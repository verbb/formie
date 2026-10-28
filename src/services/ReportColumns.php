<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\ReportSubfieldsInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Content;
use verbb\formie\fields\Heading;
use verbb\formie\fields\Html;
use verbb\formie\fields\Note;
use verbb\formie\fields\Section;
use verbb\formie\fields\Summary;
use verbb\formie\helpers\Table;
use verbb\formie\models\Report;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\elements\User;
use craft\helpers\DateTimeHelper;

use DateTime;

class ReportColumns extends Component
{
    // Constants
    // =========================================================================

    public const FIELD_COLUMNS_MODE_ALL = 'all';
    public const FIELD_COLUMNS_MODE_SELECTED = 'selected';

    public const FIELD_ORDER_LAYOUT = 'layout';
    public const FIELD_ORDER_ALPHABETICAL = 'alphabetical';
    public const FIELD_ORDER_CUSTOM = 'custom';


    // Public Methods
    // =========================================================================

    public function getAttributeDefinitions(): array
    {
        return [
            'title' => Craft::t('site', 'Title'),
            'formName' => Craft::t('site', 'Form Name'),
            'status' => Craft::t('site', 'Status'),
            'dateCreated' => Craft::t('site', 'Date Created'),
            'dateUpdated' => Craft::t('site', 'Date Updated'),
            'id' => Craft::t('site', 'ID'),
            'ipAddress' => Craft::t('site', 'IP Address'),
            'isIncomplete' => Craft::t('site', 'Is Incomplete?'),
            'isSpam' => Craft::t('site', 'Is Spam?'),
        ];
    }

    public function getDefaultAttributeColumns(): array
    {
        $disabledByDefault = ['id', 'ipAddress'];
        $columns = [];

        foreach ($this->getAttributeDefinitions() as $handle => $label) {
            $columns[] = [
                'type' => 'attribute',
                'handle' => $handle,
                'label' => $label,
                'enabled' => !in_array($handle, $disabledByDefault, true),
            ];
        }

        return $columns;
    }

    public function getFieldColumnsForFormIds(mixed $formIds, ?User $user = null, bool $includeSubfields = true): array
    {
        $user ??= Craft::$app->getUser()->getIdentity();
        $resolvedFormIds = Formie::$plugin->getReportQuery()->resolveFormIds($formIds, $user);

        if ($resolvedFormIds === []) {
            return [];
        }

        return $this->_queryFieldColumnsForFormIds($resolvedFormIds, $includeSubfields);
    }

    public function getFieldColumnGroupsForFormIds(mixed $formIds, ?User $user = null): array
    {
        $user ??= Craft::$app->getUser()->getIdentity();
        $resolvedFormIds = Formie::$plugin->getReportQuery()->resolveFormIds($formIds, $user);

        if ($resolvedFormIds === []) {
            return [];
        }

        $siteId = (int)Craft::$app->getSites()->getPrimarySite()->id;
        $formRows = (new Query())
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
                '[[elements_sites.elementId]] = [[forms.id]] AND [[elements_sites.siteId]] = COALESCE([[forms.sourceSiteId]], :siteId)',
                [':siteId' => $siteId],
            )
            ->where([
                'elements.id' => $resolvedFormIds,
                'elements.dateDeleted' => null,
                'elements.draftId' => null,
                'elements.revisionId' => null,
            ])
            ->indexBy('id')
            ->all();

        $groupNames = [];

        foreach (Formie::$plugin->getFormGroups()->getAllGroups() as $group) {
            $groupNames[(int)$group->id] = $group->name;
        }

        $columnsByFormId = [];

        foreach ($this->_queryFieldColumnRowsForFormIds($resolvedFormIds) as $row) {
            $formId = (int)($row['formId'] ?? 0);

            if (!$formId) {
                continue;
            }

            $columnsByFormId[$formId][] = [
                'type' => 'field',
                'handle' => (string)($row['handle'] ?? ''),
                'label' => (string)($row['label'] ?? $row['handle'] ?? ''),
                'enabled' => false,
                'formId' => $formId,
            ];
        }

        $groups = [];

        foreach ($resolvedFormIds as $formId) {
            $formId = (int)$formId;
            $form = $formRows[$formId] ?? null;
            $columns = $columnsByFormId[$formId] ?? [];

            if (!$form || $columns === []) {
                continue;
            }

            $groupId = $form['groupId'] ? (int)$form['groupId'] : null;

            $groups[] = [
                'formId' => $formId,
                'formTitle' => (string)($form['title'] ?? ''),
                'formHandle' => (string)($form['handle'] ?? ''),
                'groupId' => $groupId,
                'groupName' => $groupId ? ($groupNames[$groupId] ?? null) : null,
                'columns' => $columns,
            ];
        }

        return $groups;
    }

    public function inferFieldColumnsMode(array $columns, ?array $display = null): string
    {
        $mode = $display['fieldColumnsMode'] ?? null;

        if (in_array($mode, [self::FIELD_COLUMNS_MODE_ALL, self::FIELD_COLUMNS_MODE_SELECTED], true)) {
            return $mode;
        }

        foreach ($columns as $column) {
            if (!is_array($column)) {
                continue;
            }

            if (($column['type'] ?? 'attribute') === 'field' && !empty($column['enabled'])) {
                return self::FIELD_COLUMNS_MODE_SELECTED;
            }
        }

        return self::FIELD_COLUMNS_MODE_ALL;
    }

    public function usesAllFieldColumns(Report $report): bool
    {
        $settings = $report->getSettingsModel();

        return $this->inferFieldColumnsMode($settings->columns, $settings->display) === self::FIELD_COLUMNS_MODE_ALL;
    }

    public function normalizeFieldColumnsMode(mixed $mode): string
    {
        return $mode === self::FIELD_COLUMNS_MODE_SELECTED
            ? self::FIELD_COLUMNS_MODE_SELECTED
            : self::FIELD_COLUMNS_MODE_ALL;
    }

    public function getAvailableFieldColumns(Report $report, ?User $user = null): array
    {
        return $this->getFieldColumnsForFormIds(
            $report->getSettingsModel()->filters['formIds'] ?? '*',
            $user,
        );
    }

    public function inferFieldOrder(array $columns, ?array $display = null): string
    {
        $order = $display['fieldOrder'] ?? null;

        if (in_array($order, [self::FIELD_ORDER_LAYOUT, self::FIELD_ORDER_ALPHABETICAL, self::FIELD_ORDER_CUSTOM], true)) {
            return $order;
        }

        // Older reports with a saved selection already have an authored column order.
        return $this->inferFieldColumnsMode($columns, $display) === self::FIELD_COLUMNS_MODE_SELECTED
            ? self::FIELD_ORDER_CUSTOM
            : self::FIELD_ORDER_LAYOUT;
    }

    public function normalizeCompoundFieldColumns(mixed $mode): string
    {
        return in_array($mode, ['combined', 'subfields', 'both'], true) ? $mode : 'combined';
    }

    public function getAutomaticFieldColumns(array $columns, mixed $mode = null): array
    {
        $mode = $this->normalizeCompoundFieldColumns($mode);
        $parents = [];

        foreach ($columns as $column) {
            if (str_contains($column['handle'], '.')) {
                $parents[explode('.', $column['handle'], 2)[0]] = true;
            }
        }

        // Fields without declared report subcolumns retain their combined column.
        return array_values(array_filter($columns, static function(array $column) use ($mode, $parents): bool {
            if (str_contains($column['handle'], '.')) {
                return $mode !== 'combined';
            }

            return $mode !== 'subfields' || !isset($parents[$column['handle']]);
        }));
    }

    public function getConfiguredColumns(Report $report, ?User $user = null): array
    {
        $settings = $report->getSettingsModel();
        $columns = $this->usesAllFieldColumns($report)
            ? $this->_sourceColumnsForAllFieldMode($report, $settings->columns, $user)
            : $settings->columns;
        $columns = $this->normalizeColumnsPayload($columns);
        $order = $this->inferFieldOrder($settings->columns, $settings->display);

        if ($order === self::FIELD_ORDER_CUSTOM) {
            return $columns;
        }

        $fields = array_values(array_filter($columns, static fn(array $column): bool => ($column['type'] ?? 'attribute') === 'field'));

        if ($order === self::FIELD_ORDER_ALPHABETICAL) {
            usort($fields, static fn(array $a, array $b): int => strcmp(
                mb_strtolower((string)($a['label'] ?? $a['handle'])),
                mb_strtolower((string)($b['label'] ?? $b['handle'])),
            ));
        } else {
            $positions = array_flip(array_column($this->getFieldColumnsForFormIds(
                $settings->filters['formIds'] ?? '*',
                $user,
                includeSubfields: true,
            ), 'handle'));
            usort($fields, static fn(array $a, array $b): int => ($positions[$a['handle']] ?? PHP_INT_MAX) <=> ($positions[$b['handle']] ?? PHP_INT_MAX));
        }

        // Replace field slots only: attributes retain their authored positions.
        $index = 0;

        foreach ($columns as &$column) {
            if (($column['type'] ?? 'attribute') === 'field') {
                $column = $fields[$index++];
            }
        }
        unset($column);

        return $columns;
    }

    public function mergeEditorColumns(array $savedColumns, array $availableFieldColumns): array
    {
        $defaults = $this->getDefaultAttributeColumns();
        $merged = [];
        $index = [];

        foreach ([$defaults, $availableFieldColumns, $savedColumns] as $source) {
            foreach ($source as $column) {
                if (!is_array($column) || empty($column['handle'])) {
                    continue;
                }

                $type = $column['type'] ?? 'attribute';
                $key = $type . ':' . $column['handle'];

                if (isset($index[$key])) {
                    $existing = &$merged[$index[$key]];
                    $existing['label'] = $column['label'] ?? $existing['label'];
                    $existing['enabled'] = array_key_exists('enabled', $column)
                        ? (bool)$column['enabled']
                        : $existing['enabled'];

                    if (!empty($column['formId'])) {
                        $existing['formId'] = (int)$column['formId'];
                    }

                    continue;
                }

                $index[$key] = count($merged);
                $mergedColumn = [
                    'type' => $type,
                    'handle' => (string)$column['handle'],
                    'label' => $column['label'] ?? null,
                    'enabled' => (bool)($column['enabled'] ?? false),
                ];

                if (!empty($column['formId'])) {
                    $mergedColumn['formId'] = (int)$column['formId'];
                }

                $merged[] = $mergedColumn;
            }
        }

        if ($savedColumns !== []) {
            $ordered = [];
            $remaining = $merged;

            foreach ($savedColumns as $column) {
                if (!is_array($column) || empty($column['handle'])) {
                    continue;
                }

                $type = $column['type'] ?? 'attribute';
                $key = $type . ':' . $column['handle'];

                foreach ($remaining as $offset => $candidate) {
                    $candidateKey = ($candidate['type'] ?? 'attribute') . ':' . $candidate['handle'];

                    if ($candidateKey !== $key) {
                        continue;
                    }

                    $ordered[] = $candidate;
                    unset($remaining[$offset]);
                    break;
                }
            }

            return [...$ordered, ...array_values($remaining)];
        }

        return $merged;
    }

    public function resolveColumns(Report $report, ?array $columnOverride = null, ?User $user = null): array
    {
        $user ??= Craft::$app->getUser()->getIdentity();
        $settings = $report->getSettingsModel();
        $display = $settings->display;
        $definitions = $this->getAttributeDefinitions();
        $sourceColumns = $columnOverride ?? $this->getConfiguredColumns($report, $user);

        $resolved = [];

        if ($sourceColumns === []) {
            $sourceColumns = $this->getDefaultAttributeColumns();
        }

        foreach ($sourceColumns as $column) {
            if (!is_array($column) || empty($column['enabled'])) {
                continue;
            }

            $type = $column['type'] ?? 'attribute';
            $handle = (string)($column['handle'] ?? '');

            if ($handle === '') {
                continue;
            }

            if ($type === 'attribute') {
                if (!isset($definitions[$handle])) {
                    continue;
                }

                $header = $column['label'] ?? $definitions[$handle];

                if (!empty($display['useFieldHandles'])) {
                    $header = $handle;
                }
            } else {
                $header = $column['label'] ?? $handle;

                if (!empty($display['useFieldHandles'])) {
                    $header = $handle;
                }
            }

            $resolved[] = [
                'id' => $type . ':' . $handle,
                'type' => $type,
                'handle' => $handle,
                'header' => $header,
            ];
        }

        if ($resolved === [] && $columnOverride === null) {
            foreach ($this->getDefaultAttributeColumns() as $column) {
                if (empty($column['enabled'])) {
                    continue;
                }

                $handle = $column['handle'];
                $header = !empty($display['useFieldHandles']) ? $handle : ($column['label'] ?? $definitions[$handle]);

                $resolved[] = [
                    'id' => 'attribute:' . $handle,
                    'type' => 'attribute',
                    'handle' => $handle,
                    'header' => $header,
                ];
            }
        }

        return $resolved;
    }

    public function formatRow(Submission $submission, array $columns, array $display): array
    {
        $cells = [];

        foreach ($columns as $column) {
            $cells[] = $this->formatCell($submission, $column, $display);
        }

        return $cells;
    }

    public function formatViewerRow(Submission $submission, array $columns, array $display): array
    {
        $cells = [];

        foreach ($columns as $column) {
            $cells[] = $this->formatViewerCell($submission, $column, $display);
        }

        return $cells;
    }

    public function formatRowAssoc(Submission $submission, array $columns, array $display): array
    {
        $row = [];
        $headers = $this->getExportHeaders($columns);

        foreach (array_values($columns) as $index => $column) {
            $row[$headers[$index]] = $this->formatCell($submission, $column, $display);
        }

        return $row;
    }

    public function getExportHeaders(array $columns): array
    {
        $headers = array_map(static fn($column) => (string)$column['header'], array_values($columns));
        $reserved = array_fill_keys($headers, true);
        $used = [];

        foreach ($headers as &$header) {
            $label = $header;
            $suffix = 2;

            // Keep literal headings available, including labels such as "Value (2)".
            // Otherwise resolving one duplicate could overwrite a later column.
            if (isset($used[$header])) {
                do {
                    $header = $label . ' (' . $suffix++ . ')';
                } while (isset($reserved[$header]) || isset($used[$header]));
            }

            $used[$header] = true;
        }

        return $headers;
    }

    public function formatCell(Submission $submission, array $column, array $display): mixed
    {
        if (($column['type'] ?? null) === 'field') {
            return $this->_formatFieldCell($submission, $column['handle'], $display);
        }

        return $this->_formatAttributeCell($submission, $column['handle']);
    }

    public function formatViewerCell(Submission $submission, array $column, array $display): array
    {
        $handle = (string)($column['handle'] ?? '');
        $type = $column['type'] ?? 'attribute';

        if ($type === 'field') {
            return [
                'type' => 'text',
                'value' => $this->formatCell($submission, $column, $display),
            ];
        }

        if (in_array($handle, ['dateCreated', 'dateUpdated'], true)) {
            $value = $submission->$handle;

            return [
                'type' => 'datetime',
                'value' => $value instanceof DateTime
                    ? DateTimeHelper::toIso8601($value)
                    : null,
            ];
        }

        if ($handle === 'status') {
            $status = $submission->getStatusModel(true);

            return [
                'type' => 'status',
                'name' => $status->name ?? '',
                'color' => $status->color ?? 'green',
            ];
        }

        if ($handle === 'title') {
            return [
                'type' => 'link',
                'value' => $submission->title ?? '',
                'url' => $submission->getCpEditUrl() ?: null,
            ];
        }

        if ($handle === 'formName') {
            $form = $submission->getForm();

            return [
                'type' => 'link',
                'value' => $form?->title ?? '',
                'url' => $form?->getCpEditUrl() ?: null,
            ];
        }

        return [
            'type' => 'text',
            'value' => $this->_formatAttributeCell($submission, $handle),
        ];
    }

    public function normalizeColumnsPayload(array $columns): array
    {
        if ($columns === []) {
            return $this->getDefaultAttributeColumns();
        }

        $normalized = [];

        foreach ($columns as $column) {
            if (!is_array($column) || empty($column['handle'])) {
                continue;
            }

            $normalizedColumn = [
                'type' => $column['type'] ?? 'attribute',
                'handle' => (string)$column['handle'],
                'label' => $column['label'] ?? null,
                'enabled' => (bool)($column['enabled'] ?? false),
            ];

            if (!empty($column['formId'])) {
                $normalizedColumn['formId'] = (int)$column['formId'];
            }

            $normalized[] = $normalizedColumn;
        }

        return $normalized ?: $this->getDefaultAttributeColumns();
    }

    public function compactColumnsForStorage(array $columns, ?string $fieldColumnsMode = null, ?string $fieldOrder = null): array
    {
        $normalized = [];

        foreach ($this->normalizeColumnsPayload($columns) as $column) {
            $type = $column['type'] ?? 'attribute';

            if ($type !== 'attribute' && empty($column['enabled'])) {
                continue;
            }

            if (
                $fieldColumnsMode === self::FIELD_COLUMNS_MODE_ALL
                && $fieldOrder === null
                && $type === 'field'
            ) {
                continue;
            }

            $normalized[] = $column;
        }

        return $normalized ?: $this->getDefaultAttributeColumns();
    }


    // Private Methods
    // =========================================================================

    private function _sourceColumnsForAllFieldMode(Report $report, array $savedColumns, ?User $user): array
    {
        $attributes = [];

        foreach ($savedColumns as $column) {
            if (!is_array($column) || empty($column['handle'])) {
                continue;
            }

            if (($column['type'] ?? 'attribute') !== 'attribute') {
                continue;
            }

            $attributes[] = $column;
        }

        if ($attributes === []) {
            $attributes = $this->getDefaultAttributeColumns();
        }

        $settings = $report->getSettingsModel();
        $availableColumns = $this->getAutomaticFieldColumns(
            $this->getFieldColumnsForFormIds($settings->filters['formIds'] ?? '*', $user),
            $settings->display['compoundFieldColumns'] ?? null,
        );

        $fieldColumns = array_map(static function(array $column): array {
            $column['enabled'] = true;

            return $column;
        }, $availableColumns);

        // Keep saved positions and labels, while including newly added fields automatically.
        $available = array_column($fieldColumns, null, 'handle');
        $columns = [];

        $sourceColumns = empty($report->getSettingsModel()->display['fieldOrder']) ? $attributes : ($savedColumns ?: $attributes);

        foreach ($sourceColumns as $column) {
            if (($column['type'] ?? 'attribute') !== 'field') {
                $columns[] = $column;
            } elseif (isset($available[$column['handle']])) {
                $columns[] = array_merge($available[$column['handle']], $column, ['enabled' => true]);
                unset($available[$column['handle']]);
            }
        }

        return [...$columns, ...array_values($available)];
    }

    private function _queryFieldColumnRowsForFormIds(array $formIds, bool $includeSubfields = true): array
    {
        // Automatic columns follow each form's layout, including reordered pages and rows.
        $rows = (new Query())
            ->select([
                'fieldId' => 'ff.id',
                'fieldType' => 'f.type',
                'formId' => 'fo.id',
                'handle' => 'f.handle',
                'label' => 'f.label',
            ])
            ->from(['ff' => Table::FORMIE_FORM_FIELDS])
            ->innerJoin(['f' => Table::FORMIE_FIELDS], '[[f.id]] = [[ff.fieldId]]')
            ->innerJoin(['fo' => Table::FORMIE_FORMS], '[[fo.layoutId]] = [[ff.layoutId]]')
            ->innerJoin(['r' => Table::FORMIE_FIELD_LAYOUT_ROWS], '[[r.id]] = [[ff.rowId]]')
            ->innerJoin(['p' => Table::FORMIE_FIELD_LAYOUT_PAGES], '[[p.id]] = [[r.pageId]]')
            ->where(['fo.id' => $formIds])
            ->andWhere(['not in', 'f.type', $this->_cosmeticFieldTypes()])
            ->orderBy([
                'fo.id' => SORT_ASC,
                'p.sortOrder' => SORT_ASC,
                'r.sortOrder' => SORT_ASC,
                'ff.sortOrder' => SORT_ASC,
                'f.handle' => SORT_ASC,
            ])
            ->all();

        if (!$includeSubfields) {
            return $rows;
        }

        $columns = [];

        foreach ($rows as $row) {
            $columns[] = $row;

            if (!is_a($row['fieldType'], ReportSubfieldsInterface::class, true)) {
                continue;
            }

            // Resolve the form instance so enabled parts and custom labels come
            // from its saved nested layout, including synced field settings.
            $field = Formie::$plugin->getFields()->getFieldById((int)$row['fieldId']);

            if (!$field instanceof ReportSubfieldsInterface) {
                continue;
            }

            foreach ($this->_getReportSubfields($field) as $child) {
                $columns[] = [
                    'formId' => $row['formId'],
                    'handle' => $row['handle'] . '.' . $child->handle,
                    'label' => $row['label'] . ': ' . $child->label,
                ];
            }
        }

        return $columns;
    }

    private function _queryFieldColumnsForFormIds(array $formIds, bool $includeSubfields = true): array
    {
        $columns = [];
        $seen = [];

        foreach ($this->_queryFieldColumnRowsForFormIds($formIds, $includeSubfields) as $row) {
            $handle = (string)($row['handle'] ?? '');

            if ($handle === '' || isset($seen[$handle])) {
                continue;
            }

            $seen[$handle] = true;
            $columns[] = [
                'type' => 'field',
                'handle' => $handle,
                'label' => (string)($row['label'] ?? $handle),
                'enabled' => false,
            ];
        }

        return $columns;
    }

    private function _getReportSubfields(ReportSubfieldsInterface $field): array
    {
        return array_values(array_filter(
            $field->getReportSubfields(),
            static fn($child): bool =>
            $child instanceof FieldInterface && !$child->getIsDisabled() && !$child->getIsCosmetic()
        ));
    }

    private function _cosmeticFieldTypes(): array
    {
        return [
            Heading::class,
            Section::class,
            Html::class,
            Content::class,
            Note::class,
            Summary::class,
        ];
    }

    private function _formatAttributeCell(Submission $submission, string $handle): mixed
    {
        if ($handle === 'status') {
            return $submission->getStatusModel(true)->name ?? null;
        }

        $value = $submission->$handle;

        if ($value instanceof DateTime) {
            return DateTimeHelper::toIso8601($value) ?: null;
        }

        if (is_bool($value)) {
            return $value ? Craft::t('app', 'Yes') : Craft::t('app', 'No');
        }

        return $value;
    }

    private function _formatFieldCell(Submission $submission, string $handle, array $display): mixed
    {
        $form = $submission->getForm();
        [$rootHandle, $childHandle] = array_pad(explode('.', $handle, 2), 2, null);
        $field = $form?->getFieldByHandle($rootHandle);

        if ($childHandle !== null) {
            if (!$field instanceof ReportSubfieldsInterface) {
                return null;
            }

            // Only explicitly declared report children may be selected, including
            // stored columns whose field layout has changed since the report was saved.
            $children = array_column($this->_getReportSubfields($field), null, 'handle');
            $field = $children[$childHandle] ?? null;
        }

        if (!$field) {
            return null;
        }

        return $submission->getFieldValueForReport($handle, (bool)($display['useOptionLabels'] ?? true));
    }
}
