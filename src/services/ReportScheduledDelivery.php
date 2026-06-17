<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\models\Report;
use verbb\formie\models\ScheduledReport;
use verbb\formie\models\ScheduledReportDelivery;

use Craft;
use craft\base\Component;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
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

        $subject = $this->resolveSubject($report, $scheduledReport, $delivery, $testSend);
        $htmlBody = $this->renderSummaryHtml($report, $scheduledReport, $delivery, $testSend);
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

        $attachment = $this->_createExportAttachment($report, $scheduledReport, $delivery, $testSend);

        if ($attachment) {
            $message->attach($attachment['path'], [
                'fileName' => $attachment['filename'],
                'contentType' => $attachment['mimeType'],
            ]);
        }

        if (!$mailer->send($message)) {
            if ($attachment && is_file($attachment['path'])) {
                @unlink($attachment['path']);
            }

            throw new \RuntimeException(Craft::t('formie', 'Couldn’t send scheduled report email.'));
        }

        if ($attachment && is_file($attachment['path'])) {
            @unlink($attachment['path']);
        }

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
    ): string {
        $view = Craft::$app->getView();
        $renderVariables = $this->_getSummaryRenderVariables($report, $scheduledReport, $delivery, $testSend);
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

    private function _createExportAttachment(
        Report $report,
        ScheduledReport $scheduledReport,
        ScheduledReportDelivery $delivery,
        bool $testSend,
    ): ?array {
        $format = $delivery->format ?: 'csv';
        $query = $this->buildSubmissionQuery($report, $scheduledReport, $testSend);

        try {
            $export = Formie::$plugin->getReportExport()->export($report, $format, $query);
        } catch (\Throwable $e) {
            Formie::error('Scheduled report export failed: {message}', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        $path = $export['path'] ?? null;
        $size = is_file($path) ? (int)filesize($path) : 0;
        $maxAttachmentSize = Formie::$plugin->getSettings()->getMaxEmailAttachmentSizeBytes();

        if ($maxAttachmentSize !== null && $size > $maxAttachmentSize) {
            @unlink($path);

            Formie::warning('Scheduled report attachment exceeded the email attachment limit ({size} bytes).', [
                'size' => $size,
            ]);

            return null;
        }

        return [
            'path' => $path,
            'filename' => $export['filename'] ?? $this->resolveAttachmentFilename($report, $format),
            'mimeType' => $export['mimeType'] ?? 'application/octet-stream',
        ];
    }

    private function _getSummaryRenderVariables(
        Report $report,
        ScheduledReport $scheduledReport,
        ScheduledReportDelivery $delivery,
        bool $testSend,
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
