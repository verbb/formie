<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\helpers\Variables;
use verbb\formie\jobs\ExportReport;
use verbb\formie\models\Report;
use verbb\formie\models\ReportExportFile;

use Craft;
use craft\base\Component;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\FileHelper;
use craft\helpers\Queue;
use craft\mail\Message;
use craft\web\BaseSpreadsheetResponseFormatter;
use craft\web\CsvResponseFormatter;
use craft\web\Response as CraftResponse;

use DateTime;

class ReportExport extends Component
{
    // Constants
    // =========================================================================

    public const DEFAULT_CHUNK_SIZE = 100;


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

        if ($this->_supportsStreaming($format)) {
            return $this->_streamSpreadsheetExport(
                $tempPath,
                $report,
                $format,
                $query,
                $chunkSize,
                $columnOverride,
                $downloadFilename,
            );
        }

        $headers = $this->_resolveExportHeaders($report, $columnOverride);
        $rows = $this->_collectRows($report, $query, $chunkSize, $columnOverride);

        $result = match ($format) {
            'json' => $this->_writeJsonExport($tempPath, $rows),
            'xml' => $this->_writeXmlExport($tempPath, $rows),
            default => $this->_writeSpreadsheetExport($tempPath, $rows, $headers, $format),
        };

        $result['filename'] = $downloadFilename;

        return $result;
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

    public function runQueuedExport(ReportExportFile $exportFile, ?callable $progressCallback = null): array
    {
        $report = Formie::$plugin->getReports()->getReportById((int)$exportFile->reportId);

        if (!$report) {
            throw new \RuntimeException(Craft::t('formie', 'Report not found.'));
        }

        $context = $exportFile->context ?? [];
        $query = $this->_buildQueryFromContext($report, $context);
        $columnOverride = $context['columnOverride'] ?? null;
        $format = $this->_normalizeFormat($exportFile->format);

        $rowCount = $this->getExportRowCount($query);
        $processed = 0;

        $progress = function(int $batchCount) use ($progressCallback, $rowCount, &$processed): void {
            $processed += $batchCount;

            if ($progressCallback && $rowCount > 0) {
                $progressCallback($processed / $rowCount);
            }
        };

        return $this->export(
            report: $report,
            format: $format,
            query: $query,
            columnOverride: $columnOverride,
        );
    }

    public function sendReadyNotification(ReportExportFile $exportFile): bool
    {
        $email = trim((string)($exportFile->notifyEmail ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $report = Formie::$plugin->getReports()->getReportById((int)$exportFile->reportId);

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

        $htmlBody = Craft::t('formie', '<p>Your export for <strong>{report}</strong> is ready.</p><p><a href="{url}">Download export</a></p><p style="color:#666;">This link expires on {date}.</p>', [
            'report' => $report->name,
            'url' => $downloadUrl,
            'date' => Craft::$app->getFormatter()->asDatetime($exportFile->dateExpires, 'short'),
        ]);

        $textBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

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
        $variables = Variables::getContextVariables($date);

        $template = (string)preg_replace_callback('/\{[^{}]+\}/', function(array $matches) use ($variables): string {
            return Variables::resolveContextReference((string)($matches[0] ?? ''), $variables);
        }, $template);

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
    ): array {
        return array_filter([
            'viewer' => $viewer,
            'columnOverride' => $columnOverride,
            'since' => $since,
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

    private function _buildQueryFromContext(Report $report, array $context): ElementQueryInterface
    {
        if (!empty($context['since'])) {
            $query = Formie::$plugin->getReportQuery()->buildSubmissionQuery($report);
            $query->dateCreated('>= ' . $context['since']);

            return $query;
        }

        $viewer = is_array($context['viewer'] ?? null) ? $context['viewer'] : [];

        return Formie::$plugin->getReportQuery()->buildViewerQuery($report, null, $viewer);
    }

    private function _supportsStreaming(string $format): bool
    {
        return in_array($format, ['csv', 'text'], true);
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
        return Craft::$app->getPath()->getTempPath()
            . DIRECTORY_SEPARATOR
            . $basename
            . '-'
            . uniqid('', true)
            . '.'
            . $extension;
    }

    /**
     * Stream CSV/tab-delimited rows straight to disk so large exports stay memory-bounded.
     */
    private function _streamSpreadsheetExport(
        string $path,
        Report $report,
        string $format,
        ElementQueryInterface $query,
        int $chunkSize,
        ?array $columnOverride,
        string $downloadFilename,
    ): array {
        $columns = Formie::$plugin->getReportColumns()->resolveColumns($report, $columnOverride);
        $headers = array_column($columns, 'header');
        $display = $report->getSettingsModel()->display;
        $delimiter = $format === 'text' ? "\t" : ',';
        $handle = fopen($path, 'wb');

        if ($handle === false) {
            throw new \RuntimeException(Craft::t('formie', 'Unable to create export file.'));
        }

        // UTF-8 BOM helps Excel open streamed CSV correctly on Windows.
        if ($format === 'csv') {
            fwrite($handle, "\xEF\xBB\xBF");
        }

        if ($headers !== []) {
            fputcsv($handle, $headers, $delimiter);
        }

        $offset = 0;

        while (true) {
            $chunkQuery = clone $query;
            $submissions = $chunkQuery->limit($chunkSize)->offset($offset)->all();

            if (!$submissions) {
                break;
            }

            foreach ($submissions as $submission) {
                $assoc = Formie::$plugin->getReportColumns()->formatRowAssoc($submission, $columns, $display);
                $row = [];

                foreach ($headers as $header) {
                    $row[] = (string)($assoc[$header] ?? '');
                }

                fputcsv($handle, $row, $delimiter);
            }

            $offset += $chunkSize;

            if (count($submissions) < $chunkSize) {
                break;
            }
        }

        fclose($handle);

        return [
            'path' => $path,
            'filename' => $downloadFilename,
            'mimeType' => $format === 'text' ? 'text/plain' : 'text/csv',
        ];
    }

    private function _collectRows(
        Report $report,
        ElementQueryInterface $query,
        int $chunkSize,
        ?array $columnOverride,
    ): array {
        $columns = Formie::$plugin->getReportColumns()->resolveColumns($report, $columnOverride);
        $display = $report->getSettingsModel()->display;
        $rows = [];
        $offset = 0;

        while (true) {
            $chunkQuery = clone $query;
            $submissions = $chunkQuery->limit($chunkSize)->offset($offset)->all();

            if (!$submissions) {
                break;
            }

            foreach ($submissions as $submission) {
                $rows[] = Formie::$plugin->getReportColumns()->formatRowAssoc($submission, $columns, $display);
            }

            $offset += $chunkSize;

            if (count($submissions) < $chunkSize) {
                break;
            }
        }

        return $this->_normaliseRows($rows);
    }

    private function _resolveExportHeaders(Report $report, ?array $columnOverride): array
    {
        $columns = Formie::$plugin->getReportColumns()->resolveColumns($report, $columnOverride);

        return array_column($columns, 'header');
    }

    private function _writeSpreadsheetExport(string $path, array $rows, array $headers, string $format): array
    {
        $response = new CraftResponse();
        $response->charset = 'UTF-8';
        $response->data = $rows;

        $formatter = match ($format) {
            'xlsx' => $this->_createXlsxFormatter(),
            'text' => Craft::createObject(CsvResponseFormatter::class, [
                'delimiter' => "\t",
                'contentType' => 'text/plain',
            ]),
            default => Craft::createObject(CsvResponseFormatter::class),
        };

        if ($rows === [] && $headers !== []) {
            $formatter->headers = $headers;
        }

        $formatter->format($response);

        if (@file_put_contents($path, $response->content) === false) {
            throw new \RuntimeException(Craft::t('formie', 'Unable to create export file.'));
        }

        return [
            'path' => $path,
            'filename' => basename($path),
            'mimeType' => match ($format) {
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text' => 'text/plain',
                default => 'text/csv',
            },
        ];
    }

    private function _createXlsxFormatter(): BaseSpreadsheetResponseFormatter
    {
        if (!class_exists('craft\\web\\XlsxResponseFormatter')) {
            throw new \RuntimeException(Craft::t('formie', 'XLSX export requires Craft CMS 5.9 or later.'));
        }

        return Craft::createObject('craft\\web\\XlsxResponseFormatter');
    }

    private function _writeJsonExport(string $path, array $rows): array
    {
        file_put_contents($path, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]');

        return [
            'path' => $path,
            'filename' => basename($path),
            'mimeType' => 'application/json',
        ];
    }

    private function _writeXmlExport(string $path, array $rows): array
    {
        $xml = new \SimpleXMLElement('<report/>');

        foreach ($rows as $row) {
            $submission = $xml->addChild('submission');

            foreach ($row as $header => $value) {
                $child = $submission->addChild('column', htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8'));
                $child->addAttribute('name', (string)$header);
            }
        }

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        $dom->save($path);

        return [
            'path' => $path,
            'filename' => basename($path),
            'mimeType' => 'application/xml',
        ];
    }

    private function _normaliseRows(array $rows): array
    {
        if (!$rows) {
            return [];
        }

        $counts = array_map('count', $rows);
        $key = array_flip($counts)[max($counts)];
        $template = array_fill_keys(array_keys($rows[$key]), '');

        return array_map(fn(array $row) => array_merge($template, $row), $rows);
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
