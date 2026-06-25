<?php
namespace verbb\formie\services;

use verbb\formie\base\FieldInterface;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\records\FieldSiteOverride as FieldSiteOverrideRecord;

use Craft;
use craft\db\Query;
use craft\helpers\Json;

use yii\base\Component;

class FieldSiteOverrides extends Component
{
    // Public Methods
    // =========================================================================

    public function isEnabled(): bool
    {
        return Craft::$app->getIsMultiSite();
    }

    public function getOverride(int $fieldId, int $siteId): array
    {
        if (!$this->isEnabled() || !$fieldId || !$siteId) {
            return [];
        }

        $record = FieldSiteOverrideRecord::findOne([
            'fieldId' => $fieldId,
            'siteId' => $siteId,
        ]);

        if (!$record) {
            return [];
        }

        $overrides = Json::decodeIfJson($record->overrides);

        if (!is_array($overrides)) {
            return [];
        }

        return $this->normalizeFieldOverride($overrides);
    }

    public function getOverridesForFieldIds(array $fieldIds, int $siteId): array
    {
        if (!$this->isEnabled() || !$siteId) {
            return [];
        }

        $fieldIds = array_values(array_unique(array_filter(array_map('intval', $fieldIds))));

        if ($fieldIds === []) {
            return [];
        }

        $rows = (new Query())
            ->select(['fieldId', 'overrides'])
            ->from(Table::FORMIE_FIELD_SITE_OVERRIDES)
            ->where([
                'siteId' => $siteId,
                'fieldId' => $fieldIds,
            ])
            ->all();

        $result = [];

        foreach ($rows as $row) {
            $fieldId = (int)$row['fieldId'];
            $overrides = Json::decodeIfJson($row['overrides'] ?? null);

            if (!is_array($overrides)) {
                continue;
            }

            $normalized = $this->normalizeFieldOverride($overrides);

            if ($normalized !== []) {
                $result[$fieldId] = $normalized;
            }
        }

        return $result;
    }

    public function getAllForForm(Form $form): array
    {
        if (!$this->isEnabled() || !$form->id) {
            return [];
        }

        $fieldIds = $this->collectFieldDefinitionIds($form);

        if ($fieldIds === []) {
            return [];
        }

        $rows = (new Query())
            ->select(['fieldId', 'siteId', 'overrides'])
            ->from(Table::FORMIE_FIELD_SITE_OVERRIDES)
            ->where(['fieldId' => $fieldIds])
            ->all();

        $result = [];

        foreach ($rows as $row) {
            $siteId = (int)$row['siteId'];
            $fieldId = (int)$row['fieldId'];
            $overrides = Json::decodeIfJson($row['overrides'] ?? null);

            if (!is_array($overrides)) {
                continue;
            }

            if ($this->isSourceSiteForForm((int)$form->id, $siteId)) {
                continue;
            }

            $normalized = $this->normalizeFieldOverride($overrides);

            if ($normalized === []) {
                continue;
            }

            $result[$siteId][$fieldId] = $normalized;
        }

        return $result;
    }

    public function saveOverrides(int $siteId, array $fieldOverridesByFieldId): void
    {
        if (!$this->isEnabled() || !$siteId || $fieldOverridesByFieldId === []) {
            return;
        }

        foreach ($fieldOverridesByFieldId as $fieldId => $override) {
            if (!is_array($override)) {
                continue;
            }

            $this->saveOverride((int)$fieldId, $siteId, $override);
        }
    }

    public function saveOverride(int $fieldId, int $siteId, array $override): void
    {
        if (!$this->isEnabled() || !$fieldId || !$siteId) {
            return;
        }

        $existing = $this->getOverride($fieldId, $siteId);
        $override = $this->normalizeFieldOverride($override);
        $override = $this->_mergeFieldOverridePayloads($existing, $override);

        if ($override === []) {
            $this->deleteOverride($fieldId, $siteId);

            return;
        }

        $record = FieldSiteOverrideRecord::findOne([
            'fieldId' => $fieldId,
            'siteId' => $siteId,
        ]) ?? new FieldSiteOverrideRecord([
            'fieldId' => $fieldId,
            'siteId' => $siteId,
        ]);

        $record->overrides = $override;
        $record->save(false);
    }

    public function deleteOverride(int $fieldId, int $siteId): void
    {
        FieldSiteOverrideRecord::deleteAll([
            'fieldId' => $fieldId,
            'siteId' => $siteId,
        ]);
    }

    public function normalizeFieldOverride(array $override): array
    {
        return Formie::$plugin->getFormSiteOverrides()->normalizeFieldOverrides([
            '_' => $override,
        ])['_'] ?? [];
    }

    public function collectFieldDefinitionIds(Form $form): array
    {
        $fieldIds = [];

        foreach ($form->getFields() as $field) {
            if ($field instanceof FieldInterface) {
                $this->_collectFieldDefinitionIds($field, $fieldIds);
            }
        }

        return array_values(array_unique(array_filter($fieldIds)));
    }

    public function collectFieldDefinitionIdsFromBuilderData(array $builderData): array
    {
        $fieldIds = [];

        foreach ($builderData['pages'] ?? [] as $page) {
            if (!is_array($page)) {
                continue;
            }

            foreach ($page['rows'] ?? [] as $row) {
                if (!is_array($row)) {
                    continue;
                }

                foreach ($row['fields'] ?? [] as $field) {
                    if (is_array($field)) {
                        $this->_collectFieldDefinitionIdsFromArray($field, $fieldIds);
                    }
                }
            }
        }

        return array_values(array_unique(array_filter($fieldIds)));
    }


    // Private Methods
    // =========================================================================

    private function isSourceSiteForForm(int $formId, int $siteId): bool
    {
        return $siteId === Formie::$plugin->getFormSiteOverrides()->getSourceSiteIdForFormId($formId);
    }

    private function _collectFieldDefinitionIds(FieldInterface $field, array &$fieldIds): void
    {
        $fieldId = (int)($field->fieldId ?: 0);

        if ($fieldId) {
            $fieldIds[] = $fieldId;
        }

        if (!$field instanceof ParentFieldInterface) {
            return;
        }

        foreach ($field->getFieldLayout()->getPages() as $page) {
            foreach ($page->getRows() as $row) {
                foreach ($row->getFields() as $nestedField) {
                    if ($nestedField instanceof FieldInterface) {
                        $this->_collectFieldDefinitionIds($nestedField, $fieldIds);
                    }
                }
            }
        }
    }

    private function _collectFieldDefinitionIdsFromArray(array $field, array &$fieldIds): void
    {
        $fieldId = $this->_getFieldDefinitionId($field);

        if ($fieldId) {
            $fieldIds[] = $fieldId;
        }

        $rowSources = [];

        if (isset($field['rows']) && is_array($field['rows'])) {
            $rowSources[] = $field['rows'];
        }

        if (isset($field['settings']['rows']) && is_array($field['settings']['rows'])) {
            $rowSources[] = $field['settings']['rows'];
        }

        foreach ($rowSources as $rows) {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                foreach ($row['fields'] ?? [] as $nestedField) {
                    if (is_array($nestedField)) {
                        $this->_collectFieldDefinitionIdsFromArray($nestedField, $fieldIds);
                    }
                }
            }
        }
    }

    private function _getFieldDefinitionId(array $field): int
    {
        $fieldId = (int)($field['fieldId'] ?? $field['settings']['fieldId'] ?? $field['syncId'] ?? $field['settings']['syncId'] ?? 0);

        return $fieldId ?: 0;
    }

    private function _mergeFieldOverridePayloads(array $existing, array $incoming): array
    {
        if ($existing === []) {
            return $incoming;
        }

        if ($incoming === []) {
            return $existing;
        }

        $merged = $existing;

        foreach ($incoming as $key => $value) {
            if ($key === 'options' && is_array($value)) {
                $merged['options'] = $value;
                continue;
            }

            if ($key === 'columns' && is_array($value)) {
                $merged['columns'] = $value;
                continue;
            }

            $merged[$key] = $value;
        }

        return $this->normalizeFieldOverride($merged);
    }
}
