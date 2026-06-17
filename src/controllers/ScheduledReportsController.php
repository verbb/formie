<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\models\ScheduledReport;
use verbb\formie\models\ScheduledReportDelivery;
use verbb\formie\services\Permissions;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\web\Response;

use yii\web\NotFoundHttpException;

class ScheduledReportsController extends SettingsAccessController
{
    // Properties
    // =========================================================================

    protected ?string $settingsPage = 'scheduled-reports';


    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (in_array($action->id, ['save', 'delete', 'test-send'], true)) {
            $this->requirePermission(Permissions::PERM_MANAGE_SCHEDULED_REPORTS);
        }

        return true;
    }

    public function actionIndex(): Response
    {
        $scheduledReports = Formie::$plugin->getScheduledReports()->getAllScheduledReports();
        $reportsById = [];

        foreach (Formie::$plugin->getReports()->getAllReports() as $report) {
            $reportsById[(int)$report->id] = $report;
        }

        return $this->renderTemplate('formie/settings/scheduled-reports/index', compact('scheduledReports', 'reportsById'));
    }

    public function actionEdit(int $id = null, ScheduledReport $scheduledReport = null): Response
    {
        $reportId = (int)$this->request->getParam('reportId');

        if (!$scheduledReport) {
            if ($id) {
                $scheduledReport = Formie::$plugin->getScheduledReports()->getScheduledReportById($id);

                if (!$scheduledReport) {
                    throw new NotFoundHttpException(Craft::t('formie', 'Scheduled report not found.'));
                }
            } else {
                $scheduledReport = new ScheduledReport([
                    'reportId' => $reportId ?: null,
                    'delivery' => (new ScheduledReportDelivery())->toStorageArray(),
                ]);
            }
        }

        $variables = compact('scheduledReport');
        $variables['reports'] = Formie::$plugin->getReports()->getAllReports();
        $variables['userGroups'] = Craft::$app->getUserGroups()->getAllGroups();
        $variables['emailTemplates'] = Formie::$plugin->getEmailTemplates()->getAllTemplates();
        $variables['title'] = $scheduledReport->id
            ? $scheduledReport->name
            : Craft::t('formie', 'Create a Scheduled Report');
        $variables['continueEditingUrl'] = $scheduledReport->id
            ? 'formie/settings/scheduled-reports/edit/' . $scheduledReport->id
            : 'formie/settings/scheduled-reports/edit/{id}';

        return $this->renderTemplate('formie/settings/scheduled-reports/_edit', $variables);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $request = $this->request;

        $scheduledReport = new ScheduledReport();
        $scheduledReport->id = $request->getBodyParam('id');
        $scheduledReport->name = $request->getBodyParam('name');
        $scheduledReport->enabled = (bool)$request->getBodyParam('enabled');
        $scheduledReport->reportId = (int)$request->getBodyParam('reportId');

        Formie::$plugin->getScheduledReports()->applyDeliveryPayload($scheduledReport, [
            'name' => $scheduledReport->name,
            'enabled' => $scheduledReport->enabled,
            'reportId' => $scheduledReport->reportId,
            'delivery' => $this->_normalizeDeliveryInput($request->getBodyParam('delivery', [])),
        ]);

        if (Formie::$plugin->getScheduledReports()->saveScheduledReport($scheduledReport)) {
            $this->setSuccessFlash(Craft::t('formie', 'Scheduled report saved.'));

            return $this->redirectToPostedUrl($scheduledReport);
        }

        $this->setFailFlash(Craft::t('formie', 'Couldn’t save scheduled report.'));
        Craft::$app->getUrlManager()->setRouteParams(compact('scheduledReport'));

        return null;
    }

    public function actionDelete(): Response
    {
        if ($this->request->getAcceptsJson()) {
            $this->requireAcceptsJson();
        } else {
            $this->requirePostRequest();
        }

        $scheduledReportId = (int)$this->request->getRequiredParam('id');
        $scheduledReport = Formie::$plugin->getScheduledReports()->getScheduledReportById($scheduledReportId);

        if (!$scheduledReport || !Formie::$plugin->getScheduledReports()->deleteScheduledReport($scheduledReport)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson(['error' => Craft::t('formie', 'Couldn’t delete scheduled report.')]);
            }

            $this->setFailFlash(Craft::t('formie', 'Couldn’t delete scheduled report.'));

            return $this->redirectToPostedUrl();
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        $this->setSuccessFlash(Craft::t('formie', 'Scheduled report deleted.'));

        return $this->redirectToPostedUrl();
    }

    public function actionTestSend(): Response
    {
        $this->requirePostRequest();

        $scheduledReportId = (int)$this->request->getRequiredParam('id');
        $scheduledReport = Formie::$plugin->getScheduledReports()->getScheduledReportById($scheduledReportId);

        if (!$scheduledReport) {
            throw new NotFoundHttpException(Craft::t('formie', 'Scheduled report not found.'));
        }

        try {
            Formie::$plugin->getReportScheduledDelivery()->send(
                $scheduledReport,
                true,
                Craft::$app->getUser()->getIdentity(),
            );
        } catch (\Throwable $e) {
            $this->setFailFlash($e->getMessage());

            return $this->redirectToPostedUrl($scheduledReport);
        }

        $this->setSuccessFlash(Craft::t('formie', 'Test email sent.'));

        return $this->redirectToPostedUrl($scheduledReport);
    }


    // Private Methods
    // =========================================================================

    private function _normalizeDeliveryInput(mixed $delivery): array
    {
        if (!is_array($delivery)) {
            return [];
        }

        $recipients = $delivery['recipients'] ?? [];

        if (is_string($recipients)) {
            $recipients = preg_split('/[\s,]+/', $recipients, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        $delivery['recipients'] = array_values(array_filter(array_map('strval', (array)$recipients)));
        $delivery['frequency'] = in_array($delivery['frequency'] ?? null, ['daily', 'weekly'], true)
            ? $delivery['frequency']
            : 'weekly';
        unset($delivery['fileName']);
        $allowedFormats = ['csv', 'json', 'xml', 'text', 'xlsx'];
        $delivery['format'] = in_array($delivery['format'] ?? null, $allowedFormats, true)
            ? $delivery['format']
            : 'csv';
        $delivery['weekday'] = max(0, min(6, (int)($delivery['weekday'] ?? 1)));
        $delivery['hour'] = max(0, min(23, (int)($delivery['hour'] ?? 8)));
        $delivery['recipientUserGroupId'] = ($delivery['recipientUserGroupId'] ?? '') !== ''
            ? (int)$delivery['recipientUserGroupId']
            : null;

        foreach (['startAt', 'endAt'] as $dateKey) {
            $date = DateTimeHelper::toDateTime($delivery[$dateKey] ?? null);
            $delivery[$dateKey] = $date ? $date->format('Y-m-d') : null;
        }

        foreach (['emailSubject', 'emailMessage'] as $stringKey) {
            $value = trim((string)($delivery[$stringKey] ?? ''));

            $delivery[$stringKey] = $value !== '' ? $value : null;
        }

        $delivery['templateId'] = ($delivery['templateId'] ?? '') !== ''
            ? (int)$delivery['templateId']
            : null;

        return $delivery;
    }
}
