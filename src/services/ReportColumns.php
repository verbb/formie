<?php
namespace verbb\formie\services;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\models\Report;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\helpers\DateTimeHelper;

use DateTime;

class ReportColumns extends Component
{
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

    public function getFieldColumnsByForm(?User $user = null): array
    {
        $user ??= Craft::$app->getUser()->getIdentity();
        $permissions = Formie::$plugin->getPermissions();
        $byForm = [];

        foreach (Form::find()->status(null)->all() as $form) {
            if (!$permissions->canViewSubmissions($user, $form)) {
                continue;
            }

            $columns = [];

            foreach ($form->getFields() as $field) {
                if ($field->getIsCosmetic()) {
                    continue;
                }

                $columns[] = [
                    'type' => 'field',
                    'handle' => $field->handle,
                    'label' => $field->label,
                    'enabled' => false,
                ];
            }

            $byForm[(string)$form->id] = $columns;
        }

        return $byForm;
    }

    public function getAvailableFieldColumns(Report $report, ?User $user = null): array
    {
        $user ??= Craft::$app->getUser()->getIdentity();
        $formIds = Formie::$plugin->getReportQuery()->resolveFormIds(
            $report->getSettingsModel()->filters['formIds'] ?? '*',
            $user,
        );
        $columns = [];
        $seen = [];

        foreach ($formIds as $formId) {
            $form = Formie::$plugin->getForms()->getFormById((int)$formId);

            if (!$form) {
                continue;
            }

            foreach ($form->getFields() as $field) {
                if ($field->getIsCosmetic()) {
                    continue;
                }

                $handle = $field->handle;

                if (isset($seen[$handle])) {
                    continue;
                }

                $seen[$handle] = true;
                $columns[] = [
                    'type' => 'field',
                    'handle' => $handle,
                    'label' => $field->label,
                    'enabled' => false,
                ];
            }
        }

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

                    continue;
                }

                $index[$key] = count($merged);
                $merged[] = [
                    'type' => $type,
                    'handle' => (string)$column['handle'],
                    'label' => $column['label'] ?? null,
                    'enabled' => (bool)($column['enabled'] ?? false),
                ];
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

    public function resolveColumns(Report $report, ?array $columnOverride = null): array
    {
        $settings = $report->getSettingsModel();
        $display = $settings->display;
        $definitions = $this->getAttributeDefinitions();
        $sourceColumns = $columnOverride ?? $settings->columns;
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

        foreach ($columns as $column) {
            $row[$column['header']] = $this->formatCell($submission, $column, $display);
        }

        return $row;
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

            $normalized[] = [
                'type' => $column['type'] ?? 'attribute',
                'handle' => (string)$column['handle'],
                'label' => $column['label'] ?? null,
                'enabled' => (bool)($column['enabled'] ?? false),
            ];
        }

        return $normalized ?: $this->getDefaultAttributeColumns();
    }


    // Private Methods
    // =========================================================================

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

        if (!$form || !$form->getFieldByHandle($handle)) {
            return null;
        }

        $value = $submission->getFieldValueForExport($handle);

        if (is_array($value)) {
            $flat = [];

            array_walk_recursive($value, function ($item) use (&$flat): void {
                if ($item !== null && $item !== '') {
                    $flat[] = (string)$item;
                }
            });

            return $flat ? implode('; ', $flat) : null;
        }

        if (!$display['useOptionLabels'] && $value !== null) {
            $stringValue = $submission->getFieldValueAsString($handle);

            return $stringValue !== '' ? $stringValue : $value;
        }

        return $value;
    }
}
