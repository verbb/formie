<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\helpers\References;
use verbb\formie\helpers\ReportDateBoundHelper;
use verbb\formie\helpers\ReportExportRows;
use verbb\formie\helpers\ReportExportWriter;
use verbb\formie\jobs\ExportReport;
use verbb\formie\models\Report;
use verbb\formie\models\ReportExportFile;
use verbb\formie\models\ReportSettings;
use verbb\formie\references\ReferenceContext;

use Craft;
use craft\base\Component;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\Queue;
use craft\mail\Message;

use yii\helpers\Markdown;

use DateTime;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

class ReportExport extends Component
{
    // Constants
    // =========================================================================

    public const DEFAULT_CHUNK_SIZE = 100;


    // Properties
    // =========================================================================

    private mixed $_progressCallback = null;
    private ?string $_workingDirectory = null;


    // Public Methods
    // =========================================================================

    public function exportCsv(
        Report $report,
        ?ElementQueryInterface $query = null,
        int $chunkSize = self::DEFAULT_CHUNK_SIZE,
        ?array $columnOverride = null,
    ): string {
        return $this->export($report, 'csv', $query, $chunkSize, $columnOverride)['path'];
    }

    public function export(
        Report $report,
        string $format,
        ?ElementQueryInterface $query = null,
        int $chunkSize = self::DEFAULT_CHUNK_SIZE,
        ?array $columnOverride = null,
    ): array {
        $format = $this->_normalizeFormat($format);
        $query ??= Formie::$plugin->getReportQuery()->buildSubmissionQuery($report);
        $query->limit(null)->offset(null);

        $date = new DateTime();
        $basename = $this->resolveBasename($report, $date);
        $extension = $this->_resolveExtension($format);
        $downloadFilename = $basename . '.' . $extension;
        $tempPath = $this->_createTempPath($basename, $extension);

        try {
            $columns = Formie::$plugin->getReportColumns()->resolveColumns($report, $columnOverride);
            $headers = Formie::$plugin->getReportColumns()->getExportHeaders($columns);
            $rows = ReportExportRows::iterate($query, $columns, $report->getSettingsModel()->display, $chunkSize, $this->_progressCallback);
            ReportExportWriter::write($tempPath, $format, $headers, $rows);

            return [
                'path' => $tempPath,
                'filename' => $downloadFilename,
                'mimeType' => match ($format) {
                    'json' => 'application/json',
                    'xml' => 'application/xml',
                    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'text' => 'text/plain',
                    default => 'text/csv',
                },
            ];
        } catch (Throwable $e) {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }

            throw $e;
        }
    }

    public function getExportRowCount(?ElementQueryInterface $query): int
    {
        if (!$query) {
            return 0;
        }

        $countQuery = clone $query;
        $countQuery->limit(null)->offset(null);

        return (int)$countQuery->count();
    }

    public function shouldQueueExport(int $rowCount): bool
    {
        $threshold = max(1, (int)Formie::$plugin->getSettings()->reportAsyncExportRowThreshold);

        return $rowCount > $threshold;
    }

    public function queueExport(
        Report $report,
        string $format,
        array $context,
        ?int $userId = null,
        ?string $notifyEmail = null,
        string $source = ReportExportFile::SOURCE_INTERACTIVE,
        ?int $scheduledReportId = null,
    ): ReportExportFile {
        $user = $userId ? Craft::$app->getUsers()->getUserById($userId) : Craft::$app->getUser()->getIdentity();

        $exportFile = Formie::$plugin->getReportExportFiles()->createPending(
            report: $report,
            format: $format,
            context: $context,
            source: $source,
            user: $user,
            scheduledReportId: $scheduledReportId,
            notifyEmail: $notifyEmail,
        );

        $settings = Formie::$plugin->getSettings();
        Queue::push(new ExportReport([
            'exportFileId' => (int)$exportFile->id,
        ]), $settings->queuePriority);

        return $exportFile;
    }

    /**
     * Builds the unsaved report behind a Submissions index export, so it shares the
     * query, column, writer and queue paths of a saved report. Form access is enforced
     * whenever the report's query or columns are resolved for a user.
     */
    public function createSubmissionExportReport(array $params): Report
    {
        $formIds = $this->_normalizeSubmissionExportFormIds($params['formIds'] ?? []);
        $form = null;

        if (is_array($formIds) && count($formIds) === 1) {
            $form = Form::find()->withoutCpIndexScope()->site('*')->unique()->id($formIds[0])->status(null)->one();

            // The report's name reaches filenames and emails, so don't reveal an inaccessible form.
            if ($form && !Formie::$plugin->getReportQuery()->canViewFormSubmissions(Craft::$app->getUser()->getIdentity(), $form)) {
                $form = null;
            }
        }

        $report = new Report([
            'name' => $form?->title ?? Craft::t('formie', 'Submissions'),
            'handle' => $form?->handle ?? 'submissions',
        ]);

        // Set every filter explicitly: report defaults cover the last month and exclude spam.
        $report->setSettingsModel(ReportSettings::fromArray([
            'filters' => [
                'formIds' => $formIds,
                'includeComplete' => filter_var($params['includeComplete'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'includeIncomplete' => filter_var($params['includeIncomplete'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'includeSpam' => filter_var($params['includeSpam'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'statusIds' => $this->_normalizeIds($params['statusIds'] ?? []),
                'siteIds' => $this->_resolveSubmissionExportSiteIds($params['siteId'] ?? null),
                'startBound' => ReportDateBoundHelper::defaultBound(),
                'endBound' => ReportDateBoundHelper::defaultBound(),
            ],
            'export' => [
                'filename' => $form ? '{handle}-submissions-{date}' : 'submissions-{date}',
            ],
        ]));

        return $report;
    }

    /**
     * Keeps requested columns in their requested order, limited to what the report offers
     * this user. With no request, every column the report enables by default is exported.
     */
    public function getSubmissionExportColumns(Report $report, ?array $requestedColumns = null, ?User $user = null): array
    {
        $columns = Formie::$plugin->getReportColumns()->getConfiguredColumns($report, $user);

        if ($requestedColumns === null) {
            return $columns;
        }

        $available = [];

        foreach ($columns as $column) {
            $available[$column['type'] . ':' . $column['handle']] = $column;
        }

        $selected = [];

        foreach ($requestedColumns as $column) {
            $key = ($column['type'] ?? 'attribute') . ':' . ($column['handle'] ?? '');

            if (isset($available[$key]) && !empty($column['enabled'])) {
                $selected[] = array_merge($available[$key], ['enabled' => true]);
            }
        }

        return $selected;
    }

    public function runQueuedExport(ReportExportFile $exportFile, ?callable $progressCallback = null): array
    {
        $report = $this->_resolveExportFileReport($exportFile);

        if (!$report) {
            throw new RuntimeException(Craft::t('formie', 'Report not found.'));
        }

        $context = $exportFile->context ?? [];
        $scheduled = $exportFile->source === ReportExportFile::SOURCE_SCHEDULED;
        $user = $exportFile->userId ? Craft::$app->getUsers()->getUserById($exportFile->userId) : null;

        // Queue workers do not inherit the requesting editor's session. Resolve
        // the owner explicitly and recheck current permissions before exporting.
        if (!$scheduled && !Formie::$plugin->getPermissions()->canExportSubmissions($user)) {
            throw new RuntimeException(Craft::t('formie', 'Report export permission is no longer available.'));
        }

        $query = $this->_buildQueryFromContext($report, $context, $user, $scheduled);
        $columnOverride = $context['columnOverride'] ?? null;
        $format = $this->_normalizeFormat($exportFile->format);

        $previousProgress = $this->_progressCallback;
        $previousDirectory = $this->_workingDirectory;
        $this->_progressCallback = $progressCallback;

        try {
            if ($exportFile->id) {
                $this->_workingDirectory = Formie::$plugin->getReportExportFiles()->getWorkingDirectory($exportFile);
                FileHelper::removeDirectory($this->_workingDirectory);
                FileHelper::createDirectory($this->_workingDirectory);
            }

            return $this->export(
                report: $report,
                format: $format,
                query: $query,
                columnOverride: $columnOverride,
            );
        } finally {
            $this->_progressCallback = $previousProgress;
            $this->_workingDirectory = $previousDirectory;
        }
    }

    public function sendReadyNotification(ReportExportFile $exportFile): bool
    {
        $email = trim((string)($exportFile->notifyEmail ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $report = $this->_resolveExportFileReport($exportFile);

        if (!$report) {
            return false;
        }

        $downloadUrl = $exportFile->getDownloadUrl();

        if (!$downloadUrl) {
            return false;
        }

        $subject = Craft::t('formie', 'Your {report} export is ready', [
            'report' => $report->name,
        ]);

        $markdownBody = implode("\n\n", [
            Craft::t('formie', 'Your export for **{report}** is ready.', [
                'report' => $report->name,
            ]),
            Craft::t('formie', '[{linkText}]({url})', [
                'linkText' => Craft::t('formie', 'Download export'),
                'url' => $downloadUrl,
            ]),
            Craft::t('formie', 'This link expires on {date}.', [
                'date' => Craft::$app->getFormatter()->asDatetime($exportFile->dateExpires, 'short'),
            ]),
        ]);

        $htmlBody = Markdown::process($markdownBody);
        $textBody = $markdownBody;

        $mailer = Craft::$app->getMailer();
        /** @var Message $message */
        $message = Craft::createObject([
            'class' => $mailer->messageClass,
            'mailer' => $mailer,
        ]);

        $message->setTo($email);
        $message->setSubject($subject);
        $message->setHtmlBody($htmlBody);
        $message->setTextBody($textBody);

        return (bool)$mailer->send($message);
    }

    public function resolveBasename(Report $report, ?DateTime $date = null): string
    {
        $date ??= new DateTime();
        $settings = $report->getSettingsModel();
        $template = trim((string)($settings->export['filename'] ?? ''));

        if ($template === '') {
            $template = 'formie-report-{handle}-{timestamp}';
        }

        $replacements = [
            '{handle}' => (string)$report->handle,
            '{name}' => $this->_sanitizeFilenameSegment((string)$report->name),
            '{date}' => $date->format('Y-m-d'),
            '{time}' => $date->format('H-i'),
            '{datetime}' => $date->format('Y-m-d-H-i'),
        ];

        $template = strtr($template, $replacements);
        $context = ReferenceContext::forReport(
            site: Craft::$app->getSites()->getCurrentSite(),
            now: DateTimeImmutable::createFromMutable($date),
            report: ['handle' => (string)$report->handle, 'name' => (string)$report->name],
        );
        $template = References::interpolateText($template, $context);

        return $this->_sanitizeFilename($template);
    }

    public function resolveFilename(Report $report, string $format, ?DateTime $date = null): string
    {
        $format = $this->_normalizeFormat($format);

        return $this->resolveBasename($report, $date) . '.' . $this->_resolveExtension($format);
    }

    public function getFilename(Report $report, ?DateTime $date = null): string
    {
        return $this->resolveFilename($report, 'csv', $date);
    }

    public function buildExportContext(
        array $viewer = [],
        ?array $columnOverride = null,
        ?string $since = null,
        ?Report $report = null,
    ): array {
        return array_filter([
            'viewer' => $viewer,
            'columnOverride' => $columnOverride,
            'since' => $since,
            // A queue worker can't look an unsaved report up, so it travels with the export.
            'report' => $report && !$report->id ? [
                'name' => $report->name,
                'handle' => $report->handle,
                'settings' => $report->settings,
            ] : null,
        ], fn(mixed $value) => $value !== null && $value !== []);
    }

    public function exceedsEmailAttachmentLimit(string $path): bool
    {
        $maxAttachmentSize = Formie::$plugin->getSettings()->getMaxEmailAttachmentSizeBytes();

        if ($maxAttachmentSize === null || !is_file($path)) {
            return false;
        }

        return (int)filesize($path) > $maxAttachmentSize;
    }


    // Private Methods
    // =========================================================================

    private function _resolveExportFileReport(ReportExportFile $exportFile): ?Report
    {
        if ($exportFile->reportId) {
            return Formie::$plugin->getReports()->getReportById($exportFile->reportId);
        }

        $definition = $exportFile->context['report'] ?? null;

        if (!is_array($definition)) {
            return null;
        }

        return new Report([
            'name' => $definition['name'] ?? null,
            'handle' => $definition['handle'] ?? null,
            'settings' => is_array($definition['settings'] ?? null) ? $definition['settings'] : null,
        ]);
    }

    private function _normalizeSubmissionExportFormIds(mixed $formIds): string|array
    {
        if (is_string($formIds)) {
            $formIds = Json::decodeIfJson($formIds);
        }

        if ($formIds === '*' || $formIds === ['*']) {
            return '*';
        }

        return $this->_normalizeIds($formIds);
    }

    private function _normalizeIds(mixed $ids): array
    {
        if (is_string($ids)) {
            $ids = Json::decodeIfJson($ids);
        }

        return array_values(array_unique(array_filter(array_map('intval', (array)$ids), static fn(int $id): bool => $id > 0)));
    }

    private function _resolveSubmissionExportSiteIds(mixed $siteId): array
    {
        if (!Craft::$app->getIsMultiSite()) {
            return [];
        }

        $sites = Craft::$app->getSites();
        $editableSiteIds = array_map('intval', $sites->getEditableSiteIds());

        if (in_array((int)$siteId, $editableSiteIds, true)) {
            return [(int)$siteId];
        }

        // An empty list means every site, so someone with no site access must still match nothing.
        return count($editableSiteIds) === count($sites->getAllSiteIds()) ? [] : ($editableSiteIds ?: [0]);
    }

    private function _buildQueryFromContext(Report $report, array $context, ?User $user = null, bool $scheduled = false): ElementQueryInterface
    {
        if (!empty($context['since'])) {
            $query = Formie::$plugin->getReportQuery()->buildSubmissionQuery($report, $user, checkPermissions: !$scheduled);
            // Queued scheduled exports must retain the same saved bounds as attachments.
            $query->andWhere(['>=', 'elements.dateCreated', $context['since']]);

            return $query;
        }

        $viewer = is_array($context['viewer'] ?? null) ? $context['viewer'] : [];

        if ($scheduled) {
            return Formie::$plugin->getReportQuery()->buildSubmissionQuery($report, checkPermissions: false);
        }

        return Formie::$plugin->getReportQuery()->buildViewerQuery($report, $user, $viewer);
    }

    private function _normalizeFormat(string $format): string
    {
        $format = strtolower(trim($format));
        $allowed = ['csv', 'json', 'xml', 'text', 'xlsx'];

        if (!in_array($format, $allowed, true)) {
            return 'csv';
        }

        return $format;
    }

    private function _createTempPath(string $basename, string $extension): string
    {
        if ($this->_workingDirectory) {
            return $this->_workingDirectory . '/export.' . $extension;
        }

        return Craft::$app->getPath()->getTempPath()
            . DIRECTORY_SEPARATOR
            . $basename
            . '-'
            . uniqid('', true)
            . '.'
            . $extension;
    }

    private function _resolveExtension(string $format): string
    {
        return match ($format) {
            'json' => 'json',
            'xml' => 'xml',
            'text' => 'txt',
            'xlsx' => 'xlsx',
            default => 'csv',
        };
    }

    private function _sanitizeFilenameSegment(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return 'report';
        }

        return $this->_sanitizeFilename($value);
    }

    private function _sanitizeFilename(string $value): string
    {
        $value = preg_replace('/\.[a-z0-9]{1,8}$/i', '', $value) ?: $value;
        $value = FileHelper::sanitizeFilename($value, [
            'separator' => '-',
        ]);

        return $value !== '' ? $value : 'formie-report';
    }
}
