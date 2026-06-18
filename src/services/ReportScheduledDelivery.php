<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use craft\helpers\Db;
use verbb\formie\models\Report;
use verbb\formie\models\ReportExportFile;
use verbb\formie\models\ScheduledReport;
use verbb\formie\models\ScheduledReportDelivery;

use Craft;
use craft\base\Component;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
use craft\helpers\Template;
use craft\mail\Message;

use DateTime;

class ReportScheduledDelivery extends Component
{
    // Public Methods
    // =========================================================================

    public function send(ScheduledReport $scheduledReport, bool $testSend = false, ?User $triggeredBy = null): bool
    {
        $report = Formie::$plugin->getReports()->getReportById((int)$scheduledReport->reportId);

        if (!$report) {
            throw new \RuntimeException(Craft::t('formie', 'Scheduled report references a missing report.'));
        }

        $delivery = $scheduledReport->getDeliveryModel();
        $recipients = $testSend
            ? $this->resolveTestRecipients($triggeredBy)
            : $this->resolveRecipients($delivery);

        if (!$recipients) {
            throw new \RuntimeException(Craft::t('formie', 'Scheduled report has no recipients.'));
        }

        $exportResult = $this->_createExportDelivery($report, $scheduledReport, $delivery, $testSend);
        $subject = $this->resolveSubject($report, $scheduledReport, $delivery, $testSend);
        $htmlBody = $this->renderSummaryHtml(
            $report,
            $scheduledReport,
            $delivery,
            $testSend,
            $exportResult,
        );
        $textBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $mailer = Craft::$app->getMailer();
        /** @var Message $message */
        $message = Craft::createObject([
            'class' => $mailer->messageClass,
            'mailer' => $mailer,
        ]);

        $message->setTo($recipients);
        $message->setSubject($subject);
        $message->setHtmlBody($htmlBody);
        $message->setTextBody($textBody);

        if (($exportResult['type'] ?? null) === 'attachment') {
            $attachment = $exportResult['attachment'];
            $message->attach($attachment['path'], [
                'fileName' => $attachment['filename'],
                'contentType' => $attachment['mimeType'],
            ]);
        }

        if (!$mailer->send($message)) {
            $this->_cleanupExportResult($exportResult);

            throw new \RuntimeException(Craft::t('formie', 'Couldn’t send scheduled report email.'));
        }

        $this->_cleanupExportResult($exportResult, keepLinkedExport: true);

        if (!$testSend) {
            Formie::$plugin->getScheduledReports()->markSent($scheduledReport);
        }

        return true;
    }

    public function resolveRecipients(ScheduledReportDelivery $delivery): array
    {
        $recipients = [];

        foreach ($delivery->recipients as $recipient) {
            $email = trim((string)$recipient);

            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = $email;
            }
        }

        if ($delivery->recipientUserGroupId) {
            $users = User::find()
                ->groupId($delivery->recipientUserGroupId)
                ->status(null)
                ->all();

            foreach ($users as $user) {
                if ($user->email) {
                    $recipients[] = $user->email;
                }
            }
        }

        return array_values(array_unique($recipients));
    }

    public function resolveTestRecipients(?User $triggeredBy = null): array
    {
        $triggeredBy ??= Craft::$app->getUser()->getIdentity();

        if ($triggeredBy?->email) {
            return [$triggeredBy->email];
        }

        return [];
    }

    public function resolveSubject(
        Report $report,
        ScheduledReport $scheduledReport,
        ScheduledReportDelivery $delivery,
        bool $testSend = false,
    ): string {
        $subject = trim((string)($delivery->emailSubject ?? ''));

        if ($subject === '') {
            $subject = Craft::t('formie', '{report} report', [
                'report' => $report->name,
            ]);
        }

        if ($testSend) {
            $subject = Craft::t('formie', '[Test] {subject}', [
                'subject' => $subject,
            ]);
        }

        return $subject;
    }

    public function resolveAttachmentFilename(Report $report, string $format = 'csv'): string
    {
        return Formie::$plugin->getReportExport()->resolveFilename($report, $format);
    }

    public function renderSummaryHtml(
        Report $report,
        ScheduledReport $scheduledReport,
        ScheduledReportDelivery $delivery,
        bool $testSend = false,
        ?array $exportResult = null,
    ): string {
        $view = Craft::$app->getView();
        $renderVariables = $this->_getSummaryRenderVariables($report, $scheduledReport, $delivery, $testSend, $exportResult);
        $contentHtml = $view->renderTemplate('formie/reports/_email/summary', $renderVariables);
        $renderVariables['contentHtml'] = Template::raw($contentHtml);

        return $this->_renderWithEmailTemplate($delivery, $renderVariables);
    }

    public function buildSubmissionQuery(
        Report $report,
        ScheduledReport $scheduledReport,
        bool $testSend = false,
    ): ElementQueryInterface {
        $query = Formie::$plugin->getReportQuery()->buildSubmissionQuery($report);

        if (!$testSend && $scheduledReport->lastSentAt) {
            $query->dateCreated('>= ' . Db::prepareDateForDb($scheduledReport->lastSentAt));
        }

        return $query;
    }


    // Private Methods
    // =========================================================================

    /**
     * Prefer email attachments for small exports; fall back to a signed download link when over the limit.
     */
    private function _createExportDelivery(
        Report $report,
        ScheduledReport $scheduledReport,
        ScheduledReportDelivery $delivery,
        bool $testSend,
    ): array {
        $format = $delivery->format ?: 'csv';
        $query = $this->buildSubmissionQuery($report, $scheduledReport, $testSend);

        try {
            $export = Formie::$plugin->getReportExport()->export($report, $format, $query);
        } catch (\Throwable $e) {
            Formie::error('Scheduled report export failed: {message}', [
                'message' => $e->getMessage(),
            ]);

            return ['type' => 'none'];
        }

        $path = $export['path'] ?? null;

        if (!$path || !is_file($path)) {
            return ['type' => 'none'];
        }

        if (!Formie::$plugin->getReportExport()->exceedsEmailAttachmentLimit($path)) {
            return [
                'type' => 'attachment',
                'attachment' => [
                    'path' => $path,
                    'filename' => $export['filename'] ?? $this->resolveAttachmentFilename($report, $format),
                    'mimeType' => $export['mimeType'] ?? 'application/octet-stream',
                ],
            ];
        }

        $since = (!$testSend && $scheduledReport->lastSentAt)
            ? DateTimeHelper::toDateTime($scheduledReport->lastSentAt)?->format('Y-m-d H:i:s')
            : null;

        $exportFile = Formie::$plugin->getReportExportFiles()->createPending(
            report: $report,
            format: $format,
            context: Formie::$plugin->getReportExport()->buildExportContext([], null, $since),
            source: ReportExportFile::SOURCE_SCHEDULED,
            scheduledReportId: (int)$scheduledReport->id,
        );

        $exportFile = Formie::$plugin->getReportExportFiles()->markReady(
            $exportFile,
            $path,
            $export['filename'] ?? $this->resolveAttachmentFilename($report, $format),
            $export['mimeType'] ?? 'application/octet-stream',
        );

        return [
            'type' => 'link',
            'exportFile' => $exportFile,
            'downloadUrl' => $exportFile->getDownloadUrl(),
            'fileSize' => is_file($path) ? (int)filesize($path) : null,
        ];
    }

    private function _cleanupExportResult(array $exportResult, bool $keepLinkedExport = false): void
    {
        if (($exportResult['type'] ?? null) === 'attachment') {
            $path = $exportResult['attachment']['path'] ?? null;

            if ($path && is_file($path)) {
                @unlink($path);
            }

            return;
        }

        if (($exportResult['type'] ?? null) === 'link' && !$keepLinkedExport) {
            $exportFile = $exportResult['exportFile'] ?? null;

            if ($exportFile instanceof ReportExportFile) {
                Formie::$plugin->getReportExportFiles()->deleteExportFile($exportFile);
            }
        }
    }

    private function _getSummaryRenderVariables(
        Report $report,
        ScheduledReport $scheduledReport,
        ScheduledReportDelivery $delivery,
        bool $testSend,
        ?array $exportResult = null,
    ): array {
        $since = (!$testSend && $scheduledReport->lastSentAt) ? $scheduledReport->lastSentAt : null;

        return [
            'report' => $report,
            'scheduledReport' => $scheduledReport,
            'summary' => Formie::$plugin->getReportQuery()->getSummaryCounts($report, null, $since),
            'viewUrl' => $report->getCpRunUrl(),
            'periodLabel' => $this->_resolvePeriodLabel($scheduledReport, $testSend),
            'message' => trim((string)($delivery->emailMessage ?? '')),
            'testSend' => $testSend,
            'exportDownloadUrl' => ($exportResult['type'] ?? null) === 'link' ? ($exportResult['downloadUrl'] ?? null) : null,
            'exportTooLargeForAttachment' => ($exportResult['type'] ?? null) === 'link',
            'exportFileSizeLabel' => isset($exportResult['fileSize'])
                ? Craft::$app->getFormatter()->asShortSize((int)$exportResult['fileSize'])
                : null,
        ];
    }

    private function _renderWithEmailTemplate(ScheduledReportDelivery $delivery, array $renderVariables): string
    {
        $view = Craft::$app->getView();
        $templatePath = '';

        if ($delivery->templateId) {
            $emailTemplate = Formie::$plugin->getEmailTemplates()->getTemplateById($delivery->templateId);

            if ($emailTemplate) {
                if (!$view->doesTemplateExist($emailTemplate->template)) {
                    Formie::error('Scheduled report email template does not exist at “{templatePath}”.', [
                        'templatePath' => $emailTemplate->template,
                    ]);
                } else {
                    $templatePath = $emailTemplate->template;
                }
            }
        }

        if ($templatePath) {
            $oldTemplatesPath = $view->getTemplatesPath();
            $view->setTemplatesPath(Craft::$app->getPath()->getSiteTemplatesPath());
            $body = $view->renderTemplate($templatePath, $renderVariables);
            $view->setTemplatesPath($oldTemplatesPath);

            return $body;
        }

        $oldTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode($view::TEMPLATE_MODE_CP);
        $body = $view->renderTemplate('formie/_special/email-template', $renderVariables);
        $view->setTemplateMode($oldTemplateMode);

        return $body;
    }

    private function _resolvePeriodLabel(ScheduledReport $scheduledReport, bool $testSend): string
    {
        if ($testSend) {
            return Craft::t('formie', 'Test send (current report filters)');
        }

        if ($scheduledReport->lastSentAt) {
            return Craft::t('formie', 'Since {date}', [
                'date' => Craft::$app->getFormatter()->asDatetime($scheduledReport->lastSentAt, 'short'),
            ]);
        }

        return Craft::t('formie', 'All matching submissions');
    }
}
