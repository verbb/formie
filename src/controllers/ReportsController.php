<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\helpers\Plugin;
use verbb\formie\models\Report;
use verbb\formie\services\Permissions;
use verbb\formie\services\ReportExport;

use Craft;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\Response;

use yii\web\NotFoundHttpException;

class ReportsController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;


    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (in_array($action->id, ['save', 'delete'], true)) {
            $this->requirePermission(Permissions::PERM_MANAGE_REPORTS);
        } elseif ($action->id === 'create' && $this->request->getIsPost()) {
            $this->requirePermission(Permissions::PERM_MANAGE_REPORTS);
        } else {
            $this->requirePermission(Permissions::PERM_ACCESS_REPORTS);
        }

        return true;
    }

    public function actionIndex(?string $reportHandle = null): Response
    {
        $request = Craft::$app->getRequest();
        $legacyReport = $request->getParam('report');

        if ($legacyReport !== null && $legacyReport !== '') {
            if (is_numeric($legacyReport)) {
                $report = Formie::$plugin->getReports()->getReportById((int)$legacyReport);
            } else {
                $report = Formie::$plugin->getReports()->getReportByHandle((string)$legacyReport);
            }

            if ($report) {
                return $this->redirect($report->getCpRunUrl());
            }
        }

        if ($request->getParam('create')) {
            return $this->redirect(UrlHelper::cpUrl('formie/reports/create'));
        }

        if ($reportHandle !== null && $reportHandle !== '') {
            $report = Formie::$plugin->getReports()->getReportByHandle($reportHandle);

            if (!$report) {
                throw new NotFoundHttpException(Craft::t('formie', 'Report not found.'));
            }
        } else {
            $reports = Formie::$plugin->getReports()->getAllReports();

            if ($reports) {
                return $this->redirect($reports[0]->getCpRunUrl());
            }
        }

        return $this->_renderDashboard($reportHandle);
    }

    public function actionEdit(int $id = null, Report $report = null): Response
    {
        if (!$report && !$id) {
            return $this->redirect(UrlHelper::cpUrl('formie/reports/create'));
        }

        $routeParams = Craft::$app->getUrlManager()->getRouteParams();
        $postedSettings = $routeParams['postedSettings'] ?? null;

        if (!$report) {
            $report = Formie::$plugin->getReports()->getReportById($id);

            if (!$report) {
                throw new NotFoundHttpException(Craft::t('formie', 'Report not found.'));
            }
        }

        $variables = compact('report');
        $variables['canEdit'] = Craft::$app->getConfig()->getGeneral()->allowAdminChanges
            && Craft::$app->getUser()->checkPermission(Permissions::PERM_MANAGE_REPORTS);
        $variables['fullPageForm'] = $variables['canEdit'];
        $variables['title'] = $report->name;
        $editorConfig = Formie::$plugin->getReportEditor()->getEditorConfig($report);

        if (is_array($postedSettings)) {
            $editorConfig['values'] = $postedSettings;
        }

        if ($report->hasErrors()) {
            $editorConfig['errors'] = $report->getErrors();
        }

        Plugin::registerCpReportsAssets();
        $this->view->registerJs('new Craft.Formie.Reports(' . Json::encode($editorConfig) . ');');

        $variables['reportSettingsPayload'] = Json::encode($editorConfig['values']);
        $variables['continueEditingUrl'] = 'formie/reports/edit/' . $report->id;

        return $this->renderTemplate('formie/reports/edit', $variables);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $request = $this->request;

        $report = new Report();
        $report->id = $request->getBodyParam('id');
        $report->name = $request->getBodyParam('name');
        $report->handle = $request->getBodyParam('handle');

        $postedSettings = null;

        if ($report->id) {
            $existingReport = Formie::$plugin->getReports()->getReportById((int)$report->id);
            $report->sortOrder = $existingReport?->sortOrder;

            $postedSettings = $this->_decodeSettingsPayload();

            if ($postedSettings === null) {
                return $this->_failSave(
                    $report,
                    null,
                    Craft::t('formie', 'Invalid report settings payload.'),
                );
            }

            if (!Formie::$plugin->getReportEditor()->applyPayload($report, $postedSettings)) {
                return $this->_failSave($report, $postedSettings);
            }
        } else {
            $report->setSettingsModel(new \verbb\formie\models\ReportSettings());
        }

        if (Formie::$plugin->getReports()->saveReport($report)) {
            $this->setSuccessFlash(Craft::t('formie', 'Report saved.'));

            return $this->redirectToPostedUrl($report);
        }

        return $this->_failSave($report, $postedSettings);
    }

    public function actionCreate(): Response
    {
        if (!$this->request->getIsPost()) {
            $user = Craft::$app->getUser()->getIdentity();
            $canManageReports = Craft::$app->getConfig()->getGeneral()->allowAdminChanges
                && Formie::$plugin->getPermissions()->canManageReports($user);

            if (!$canManageReports) {
                return $this->redirect(UrlHelper::cpUrl('formie/reports'));
            }

            return $this->_renderDashboard(openCreate: true);
        }

        $this->requirePostRequest();

        $report = new Report();
        $report->name = trim((string)$this->request->getRequiredBodyParam('name'));
        $report->handle = trim((string)$this->request->getRequiredBodyParam('handle'));

        $formIds = $this->request->getBodyParam('formIds', []);

        if (is_string($formIds)) {
            $formIds = Json::decodeIfJson($formIds) ?? [];
        }

        $formIds = array_values(array_filter(array_map('intval', (array)$formIds)));

        if ($formIds === []) {
            return $this->asJson([
                'success' => false,
                'errors' => [
                    'formIds' => [Craft::t('formie', 'Choose at least one form.')],
                ],
            ]);
        }

        $settings = new \verbb\formie\models\ReportSettings();
        $settings->filters['formIds'] = $formIds;
        $settings->columns = Formie::$plugin->getReportColumns()->getDefaultAttributeColumns();
        $report->setSettingsModel($settings);

        try {
            if (!Formie::$plugin->getReports()->saveReport($report)) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $report->getErrors(),
                ]);
            }
        } catch (\yii\db\IntegrityException) {
            return $this->asJson([
                'success' => false,
                'errors' => [
                    'handle' => [Craft::t('formie', 'That handle is already in use.')],
                ],
            ]);
        }

        return $this->asJson([
            'success' => true,
            'report' => [
                'id' => (int)$report->id,
                'name' => $report->name,
                'handle' => $report->handle,
            ],
            'viewConfig' => Formie::$plugin->getReportViewer()->getViewConfig($report),
            'redirect' => $report->getCpRunUrl(),
        ]);
    }

    public function actionViewConfig(int $id): Response
    {
        $report = Formie::$plugin->getReports()->getReportById($id);

        if (!$report) {
            throw new NotFoundHttpException(Craft::t('formie', 'Report not found.'));
        }

        return $this->asJson(Formie::$plugin->getReportViewer()->getViewConfig($report));
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();

        $reportId = (int)$this->request->getRequiredParam('id');

        if (Formie::$plugin->getReports()->deleteReportById($reportId)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson(['success' => true]);
            }

            $this->setSuccessFlash(Craft::t('formie', 'Report deleted.'));

            return $this->redirectToPostedUrl();
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['error' => Craft::t('formie', 'Couldn’t delete report.')]);
        }

        $this->setFailFlash(Craft::t('formie', 'Couldn’t delete report.'));

        return $this->redirectToPostedUrl();
    }

    public function actionView(int $id): Response
    {
        $report = Formie::$plugin->getReports()->getReportById($id);

        if (!$report) {
            throw new NotFoundHttpException(Craft::t('formie', 'Report not found.'));
        }

        return $this->redirect($report->getCpRunUrl());
    }

    public function actionTableData(int $id): Response
    {
        $report = Formie::$plugin->getReports()->getReportById($id);

        if (!$report) {
            throw new NotFoundHttpException(Craft::t('formie', 'Report not found.'));
        }

        if ($this->request->getIsPost()) {
            $this->requirePostRequest();
        }

        $page = (int)$this->request->getParam('page', 1);
        $limit = (int)$this->request->getParam('limit', 20);
        $columnOverride = $this->_decodeColumnOverride();
        $viewer = $this->_decodeViewerParams();

        return $this->asJson(
            Formie::$plugin->getReportQuery()->getTableData($report, $page, $limit, null, $columnOverride, $viewer),
        );
    }

    public function actionViewerData(int $id): Response
    {
        $report = Formie::$plugin->getReports()->getReportById($id);

        if (!$report) {
            throw new NotFoundHttpException(Craft::t('formie', 'Report not found.'));
        }

        if ($this->request->getIsPost()) {
            $this->requirePostRequest();
        }

        $viewer = $this->_decodeViewerParams();
        $settings = $report->getSettingsModel();
        $chartData = Formie::$plugin->getReportQuery()->getChartData($report, null, $viewer);

        return $this->asJson([
            'summary' => Formie::$plugin->getReportQuery()->getSummaryCounts($report, null, null, $viewer),
            'chart' => [
                'enabled' => (bool)($settings->chart['enabled'] ?? true),
                'range' => $chartData['range'],
                'data' => $chartData['rows'],
            ],
        ]);
    }

    public function actionExport(int $id): Response
    {
        $this->requirePermission(Permissions::PERM_EXPORT_SUBMISSIONS);

        $report = Formie::$plugin->getReports()->getReportById($id);

        if (!$report) {
            throw new NotFoundHttpException(Craft::t('formie', 'Report not found.'));
        }

        $columnOverride = null;

        if ($this->request->getIsPost()) {
            $this->requirePostRequest();
            $columnOverride = $this->_decodeColumnOverride();
        }

        $format = strtolower(trim((string)$this->request->getParam('format', 'csv')));
        $viewer = $this->_decodeViewerParams();
        $query = Formie::$plugin->getReportQuery()->buildViewerQuery($report, null, $viewer);
        $export = Formie::$plugin->getReportExport()->export(
            report: $report,
            format: $format,
            query: $query,
            columnOverride: $columnOverride,
        );

        return Craft::$app->getResponse()->sendFile($export['path'], $export['filename'], [
            'mimeType' => $export['mimeType'],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _renderDashboard(?string $reportHandle = null, bool $openCreate = false): Response
    {
        Plugin::registerCpReportsAssets();

        $dashboardConfig = Formie::$plugin->getReportViewer()->getDashboardConfig(
            reportHandle: $reportHandle,
            openCreate: $openCreate,
        );
        $this->view->registerJs('new Craft.Formie.Reports(' . Json::encode($dashboardConfig) . ');');

        return $this->renderTemplate('formie/reports/index', [
            'title' => Craft::t('formie', 'Reports'),
            'selectedSubnavItem' => 'reports',
        ]);
    }

    private function _decodeViewerParams(): array
    {
        $params = [
            'search' => trim((string)$this->request->getBodyParam('search', $this->request->getParam('search', ''))),
            'sort' => (string)$this->request->getBodyParam('sort', $this->request->getParam('sort', 'dateCreated')),
            'sortDir' => (string)$this->request->getBodyParam('sortDir', $this->request->getParam('sortDir', 'desc')),
        ];

        foreach (['startDate', 'endDate'] as $key) {
            if ($this->request->getBodyParam($key) !== null || $this->request->getParam($key) !== null) {
                $params[$key] = $this->_decodeViewerDateParam($key);
            }
        }

        return $params;
    }

    private function _decodeViewerDateParam(string $key): ?string
    {
        if (!$this->request->getBodyParam($key) && !$this->request->getParam($key)) {
            return null;
        }

        $value = $this->request->getBodyParam($key, $this->request->getParam($key));

        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string)$value);

        return $value !== '' ? $value : null;
    }

    private function _decodeColumnOverride(): ?array
    {
        $columnsJson = $this->request->getBodyParam('columns');

        if (!is_string($columnsJson) || $columnsJson === '') {
            return null;
        }

        try {
            $decoded = Json::decode($columnsJson);
        } catch (\Throwable) {
            return null;
        }

        if (!is_array($decoded)) {
            return null;
        }

        return Formie::$plugin->getReportColumns()->normalizeColumnsPayload($decoded);
    }

    private function _decodeSettingsPayload(): ?array
    {
        $settingsJson = $this->request->getBodyParam('settings');

        if (!is_string($settingsJson) || $settingsJson === '') {
            return null;
        }

        try {
            $payload = Json::decode($settingsJson);
        } catch (\Throwable) {
            return null;
        }

        return is_array($payload) ? $payload : null;
    }

    private function _failSave(Report $report, ?array $postedSettings = null, ?string $message = null): ?Response
    {
        $this->setFailFlash($message ?? Craft::t('formie', 'Couldn’t save report.'));

        Craft::$app->getUrlManager()->setRouteParams(array_filter([
            'report' => $report,
            'postedSettings' => $postedSettings,
        ]));

        return null;
    }
}
