<?php
namespace verbb\formie\services;

use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\helpers\ReportDateBoundHelper;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\Variables;
use verbb\formie\models\Report;
use verbb\formie\models\ReportSettings;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;

class ReportEditor extends Component
{
    // Public Methods
    // =========================================================================

    public function getEditorConfig(Report $report): array
    {
        $user = Craft::$app->getUser()->getIdentity();
        $scheduledReports = $report->id
            ? Formie::$plugin->getScheduledReports()->getScheduledReportsForReport((int)$report->id)
            : [];

        return array_merge([
            'mode' => 'editor',
            'payloadInputId' => 'formie-report-settings',
            'values' => $this->getEditorValues($report),
            'formOptions' => $this->_formOptions($user),
            'statusOptions' => $this->_statusOptions(),
            'attributeColumns' => Formie::$plugin->getReportColumns()->getDefaultAttributeColumns(),
            'fieldColumnsUrl' => UrlHelper::cpUrl('formie/reports/field-columns'),
            'scheduledReports' => array_map(fn($scheduledReport) => [
                'id' => (int)$scheduledReport->id,
                'name' => $scheduledReport->name,
                'enabled' => (bool)$scheduledReport->enabled,
                'editUrl' => $scheduledReport->getCpEditUrl(),
                'lastSentAt' => $scheduledReport->lastSentAt?->format('c'),
                'frequency' => $scheduledReport->getDeliveryModel()->frequency,
            ], $scheduledReports),
            'canManageScheduled' => Formie::$plugin->getPermissions()->canManageScheduledReports($user),
            'scheduledReportsNewUrl' => $report->id
                ? UrlHelper::cpUrl('formie/settings/scheduled-reports/new', ['reportId' => $report->id])
                : null,
            'canEdit' => Craft::$app->getConfig()->getGeneral()->allowAdminChanges
                && Formie::$plugin->getPermissions()->canManageReports($user),
            'viewUrl' => $report->id ? $report->getCpRunUrl() : null,
            'dashboardUrl' => $report->id ? $report->getCpRunUrl() : null,
            'exportUrl' => $report->id && Formie::$plugin->getPermissions()->canExportSubmissions($user)
                ? UrlHelper::cpUrl('formie/reports/export/' . $report->id)
                : null,
        ], Variables::getReportExportFilenameVariableConfig());
    }

    public function getEditorValues(Report $report): array
    {
        $settings = $report->getSettingsModel();

        return [
            'name' => $report->name,
            'handle' => $report->handle,
            'filters' => ReportDateBoundHelper::migrateLegacyFilters($settings->filters),
            'columns' => Formie::$plugin->getReportColumns()->compactColumnsForStorage($settings->columns),
            'display' => $settings->display,
            'chart' => $settings->chart,
            'export' => $settings->export,
        ];
    }

    public function applyPayload(Report $report, array $payload): bool
    {
        $report->name = trim((string)($payload['name'] ?? $report->name));
        $report->handle = trim((string)($payload['handle'] ?? $report->handle));

        $settings = ReportSettings::fromArray([
            'filters' => $this->_normalizeFilters($payload['filters'] ?? []),
            'columns' => Formie::$plugin->getReportColumns()->compactColumnsForStorage(
                $payload['columns'] ?? [],
            ),
            'display' => $this->_normalizeDisplay($payload['display'] ?? []),
            'chart' => $this->_normalizeChart($payload['chart'] ?? []),
            'export' => $this->_normalizeExport($payload['export'] ?? []),
        ]);

        $report->setSettingsModel($settings);

        return true;
    }

    public function getDefaultAttributeColumns(): array
    {
        return Formie::$plugin->getReportColumns()->getDefaultAttributeColumns();
    }

    public function getFormOptions(?User $user = null): array
    {
        return $this->_formOptions($user);
    }


    // Private Methods
    // =========================================================================

    private function _formOptions(?User $user): array
    {
        $permissions = Formie::$plugin->getPermissions();
        $includeAll = $permissions->canViewSubmissions($user, null);
        $formsByGroupId = [];
        $ungrouped = [];

        foreach ($this->_queryFormOptionRows() as $row) {
            if (!$includeAll) {
                $form = Form::find()->id((int)$row['id'])->status(null)->one();

                if (!$form || !$permissions->canViewSubmissions($user, $form)) {
                    continue;
                }
            }

            $item = [
                'label' => (string)$row['title'],
                'value' => (string)$row['id'],
                'handle' => (string)$row['handle'],
                'groupId' => $row['groupId'] ? (int)$row['groupId'] : null,
                'groupName' => null,
            ];

            if ($row['groupId']) {
                $formsByGroupId[(int)$row['groupId']][] = $item;
            } else {
                $ungrouped[] = $item;
            }
        }

        $options = [];

        foreach (Formie::$plugin->getFormGroups()->getAllGroups() as $group) {
            $groupForms = $formsByGroupId[(int)$group->id] ?? [];

            foreach ($groupForms as &$groupForm) {
                $groupForm['groupName'] = $group->name;
            }
            unset($groupForm);

            array_push($options, ...$groupForms);
        }

        array_push($options, ...$ungrouped);

        return $options;
    }

    private function _queryFormOptionRows(): array
    {
        $siteId = (int)Craft::$app->getSites()->getCurrentSite()->id;

        return (new Query())
            ->select([
                'id' => 'elements.id',
                'title' => 'elements_sites.title',
                'handle' => 'forms.handle',
                'groupId' => 'forms.groupId',
            ])
            ->from(['forms' => Table::FORMIE_FORMS])
            ->innerJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[forms.id]]')
            ->innerJoin(
                ['elements_sites' => Table::ELEMENTS_SITES],
                '[[elements_sites.elementId]] = [[forms.id]] AND [[elements_sites.siteId]] = :siteId',
                [':siteId' => $siteId],
            )
            ->where([
                'elements.dateDeleted' => null,
                'elements.draftId' => null,
                'elements.revisionId' => null,
            ])
            ->orderBy(['elements_sites.title' => SORT_ASC])
            ->all();
    }

    private function _statusOptions(): array
    {
        $options = [];

        foreach (Formie::$plugin->getStatuses()->getAllStatuses() as $status) {
            $options[] = [
                'label' => $status->name,
                'value' => (string)$status->id,
            ];
        }

        return $options;
    }

    private function _normalizeFilters(array $filters): array
    {
        $defaults = ReportSettings::defaultFilters();
        $normalized = array_merge($defaults, $filters);

        $formIds = $normalized['formIds'] ?? '*';

        if ($formIds === '*' || $formIds === ['*']) {
            $normalized['formIds'] = '*';
        } elseif ($formIds === null || $formIds === []) {
            $normalized['formIds'] = [];
        } else {
            $normalized['formIds'] = array_values(array_map('intval', (array)$formIds));
        }

        $normalized['includeComplete'] = (bool)($normalized['includeComplete'] ?? true);
        $normalized['includeIncomplete'] = (bool)($normalized['includeIncomplete'] ?? true);
        $normalized['includeSpam'] = (bool)($normalized['includeSpam'] ?? false);
        $normalized['statusIds'] = array_values(array_map('intval', (array)($normalized['statusIds'] ?? [])));

        $normalized = ReportDateBoundHelper::migrateLegacyFilters($normalized);
        $normalized['startBound'] = ReportDateBoundHelper::normalizeBound($normalized['startBound'] ?? null, false);
        $normalized['endBound'] = ReportDateBoundHelper::normalizeBound($normalized['endBound'] ?? null, true);

        return $normalized;
    }

    public function normalizeFilterDateTime(mixed $value, bool $isEndDate): ?string
    {
        return $this->_normalizeFilterDateTime($value, $isEndDate);
    }

    private function _normalizeFilterDateTime(mixed $value, bool $isEndDate): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $stringValue = trim((string)$value);

        if ($stringValue === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $stringValue)) {
            $date = DateTimeHelper::toDateTime($stringValue);

            if (!$date) {
                return null;
            }

            $date->setTime(
                $isEndDate ? 23 : 0,
                $isEndDate ? 59 : 0,
                $isEndDate ? 59 : 0,
            );

            return $date->format('Y-m-d H:i:s');
        }

        $date = DateTimeHelper::toDateTime($stringValue);

        return $date ? $date->format('Y-m-d H:i:s') : null;
    }

    private function _normalizeDisplay(array $display): array
    {
        return array_merge(ReportSettings::defaultDisplay(), $display);
    }

    private function _normalizeChart(array $chart): array
    {
        return array_merge(ReportSettings::defaultChart(), $chart);
    }

    private function _normalizeExport(array $export): array
    {
        $normalized = array_merge(ReportSettings::defaultExport(), $export);
        $normalized['filename'] = trim((string)($normalized['filename'] ?? ''));

        return $normalized;
    }
}
