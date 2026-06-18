<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\helpers\ReportDateBoundHelper;
use verbb\formie\models\Report;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\helpers\UrlHelper;

class ReportViewer extends Component
{
    // Public Methods
    // =========================================================================

    public function getDashboardConfig(?User $user = null, ?string $reportHandle = null, bool $openCreate = false): array
    {
        $user ??= Craft::$app->getUser()->getIdentity();
        $reports = Formie::$plugin->getReports()->getAllReports();
        $allowAdminChanges = Craft::$app->getConfig()->getGeneral()->allowAdminChanges;
        $canManageReports = $allowAdminChanges
            && Formie::$plugin->getPermissions()->canManageReports($user);
        $canExport = Formie::$plugin->getPermissions()->canExportSubmissions($user);
        $selectedReport = $this->_resolveSelectedReport($reports, $reportHandle);
        $reportOptions = array_map(fn(Report $report) => [
            'value' => (string)$report->id,
            'label' => $report->name,
            'handle' => $report->handle,
            'editUrl' => $report->getCpEditUrl(),
            'viewUrl' => $report->getCpRunUrl(),
        ], $reports);

        return [
            'mode' => 'dashboard',
            'reports' => $reportOptions,
            'selectedReportId' => $selectedReport?->id ? (int)$selectedReport->id : null,
            'selectedReportHandle' => $selectedReport?->handle,
            'viewConfig' => $selectedReport ? $this->getViewConfig($selectedReport, $user) : null,
            'canManageReports' => $canManageReports,
            'canExport' => $canExport,
            'createActionUrl' => UrlHelper::cpUrl('formie/reports/create'),
            'viewConfigUrl' => UrlHelper::cpUrl('formie/reports/view-config'),
            'dashboardUrl' => UrlHelper::cpUrl('formie/reports'),
            'createPageUrl' => UrlHelper::cpUrl('formie/reports/create'),
            'openCreate' => $openCreate,
            'csrfTokenName' => Craft::$app->getConfig()->getGeneral()->csrfTokenName,
            'csrfTokenValue' => Craft::$app->getRequest()->getCsrfToken(),
            'reservedHandles' => ['new', 'index', 'view', 'edit', 'export', 'scheduled', 'create'],
            'reportHandles' => Formie::$plugin->getReports()->getAllReportHandles(),
            'formOptions' => array_values(array_filter(
                Formie::$plugin->getReportEditor()->getFormOptions($user),
                fn(array $option) => ($option['value'] ?? null) !== '*',
            )),
        ];
    }

    public function getViewConfig(Report $report, ?User $user = null): array
    {
        $user ??= Craft::$app->getUser()->getIdentity();
        $settings = $report->getSettingsModel();
        $columns = Formie::$plugin->getReportColumns()->resolveColumns($report);
        $exportColumns = Formie::$plugin->getReportColumns()->compactColumnsForStorage(
            $settings->columns,
            Formie::$plugin->getReportColumns()->inferFieldColumnsMode($settings->columns, $settings->display),
        );
        $formIds = $settings->filters['formIds'] ?? '*';
        $fieldColumnsMode = Formie::$plugin->getReportColumns()->inferFieldColumnsMode($settings->columns, $settings->display);

        $resolvedFilters = Formie::$plugin->getReportQuery()->resolveFilters($report);
        $chartData = Formie::$plugin->getReportQuery()->getChartData($report, $user);

        return [
            'mode' => 'viewer',
            'report' => [
                'id' => (int)$report->id,
                'name' => $report->name,
                'handle' => $report->handle,
            ],
            'defaultDateRange' => [
                'startDate' => $resolvedFilters['startDate'] ?? null,
                'endDate' => $resolvedFilters['endDate'] ?? null,
            ],
            'dateBounds' => [
                'startBound' => $resolvedFilters['startBound'] ?? ReportDateBoundHelper::defaultBound(),
                'endBound' => $resolvedFilters['endBound'] ?? ReportDateBoundHelper::defaultBound(),
            ],
            'summary' => Formie::$plugin->getReportQuery()->getSummaryCounts($report, $user),
            'chart' => [
                'enabled' => (bool)($settings->chart['enabled'] ?? true),
                'range' => $chartData['range'],
                'data' => $chartData['rows'],
            ],
            'columns' => array_map(fn(array $column) => [
                'id' => $column['id'],
                'type' => $column['type'],
                'handle' => $column['handle'],
                'header' => $column['header'],
            ], $columns),
            'exportColumns' => array_map(fn(array $column) => [
                'type' => $column['type'],
                'handle' => $column['handle'],
                'label' => $column['label'],
                'enabled' => (bool)$column['enabled'],
            ], $exportColumns),
            'viewerColumns' => array_map(fn(array $column) => [
                'type' => $column['type'],
                'handle' => $column['handle'],
                'label' => $column['label'],
                'enabled' => (bool)$column['enabled'],
            ], $exportColumns),
            'sortableColumns' => Formie::$plugin->getReportQuery()->getSortableColumns(),
            'defaultSort' => [
                'handle' => 'dateCreated',
                'dir' => 'desc',
            ],
            'display' => array_merge($settings->display, [
                'fieldColumnsMode' => $fieldColumnsMode,
            ]),
            'fieldColumnsUrl' => UrlHelper::cpUrl('formie/reports/field-columns'),
            'filterFormIds' => $formIds,
            'tableDataUrl' => UrlHelper::cpUrl('formie/reports/table-data/' . $report->id),
            'viewerDataUrl' => UrlHelper::cpUrl('formie/reports/viewer-data/' . $report->id),
            'exportUrl' => UrlHelper::cpUrl('formie/reports/export/' . $report->id),
            'asyncExportRowThreshold' => max(1, (int)Formie::$plugin->getSettings()->reportAsyncExportRowThreshold),
            'editUrl' => $report->getCpEditUrl(),
            'dashboardUrl' => $report->getCpRunUrl(),
            'canExport' => Formie::$plugin->getPermissions()->canExportSubmissions($user),
            'canEdit' => Craft::$app->getConfig()->getGeneral()->allowAdminChanges
                && Formie::$plugin->getPermissions()->canManageReports($user),
            'csrfTokenName' => Craft::$app->getConfig()->getGeneral()->csrfTokenName,
            'csrfTokenValue' => Craft::$app->getRequest()->getCsrfToken(),
            'tablePageSize' => max(1, min(100, (int)Formie::$plugin->getSettings()->reportTablePageSize)),
        ];
    }


    // Private Methods
    // =========================================================================

    private function _resolveSelectedReport(array $reports, ?string $reportHandle = null): ?Report
    {
        if (!$reports) {
            return null;
        }

        if ($reportHandle !== null && $reportHandle !== '') {
            $report = Formie::$plugin->getReports()->getReportByHandle($reportHandle);

            if ($report) {
                return $report;
            }
        }

        return $reports[0];
    }
}
