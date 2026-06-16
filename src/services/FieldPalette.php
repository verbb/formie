<?php
namespace verbb\formie\services;

use verbb\formie\fields\MissingField;
use verbb\formie\fields\CustomField;
use verbb\formie\Formie;
use verbb\formie\helpers\StringHelper;
use verbb\formie\elements\Form;
use verbb\formie\models\FormGroup;

use Craft;
use craft\base\Component;
use craft\validators\HandleValidator;

class FieldPalette extends Component
{
    // Constants
    // =========================================================================

    public const CONFIG_KEY = 'formie.fieldPalette';
    public const VERSION = 1;
    public const UNASSIGNED_HANDLE = 'unassigned';


    // Properties
    // =========================================================================

    private ?array $_resolvedPalette = null;
    private bool $_isResolvingPalette = false;


    // Public Methods
    // =========================================================================

    public function hasStoredConfig(): bool
    {
        return Craft::$app->getProjectConfig()->get(self::CONFIG_KEY, true) !== null;
    }

    public function getEditorConfig(): array
    {
        $palette = $this->getResolvedPalette();
        $fieldMeta = $this->_getFieldMetaByClass();
        $editorPalette = $this->_preparePaletteForEditor($palette, $fieldMeta);

        return [
            'payloadInputId' => 'formie-field-palette-payload',
            'canEdit' => Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
            'palette' => $editorPalette,
        ];
    }

    public function getSavePayload(): array
    {
        $palette = $this->getResolvedPalette();
        $fieldMeta = $this->_getFieldMetaByClass();

        return $this->_serializePaletteForSave(
            $this->_preparePaletteForEditor($palette, $fieldMeta),
        );
    }

    public function savePalette(array $payload): bool
    {
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            Formie::error('Field palette cannot be saved when allowAdminChanges is disabled.', __METHOD__);

            return false;
        }

        $normalized = $this->_normalizePalettePayload($payload);

        if ($normalized === null) {
            return false;
        }

        Craft::$app->getProjectConfig()->set(
            self::CONFIG_KEY,
            $normalized,
            Craft::t('formie', 'Save field palette'),
        );

        $this->_resolvedPalette = null;
        Formie::$plugin->getFields()->resetFieldRegistryCache();

        return true;
    }

    public function normalizePalettePayload(array $payload): ?array
    {
        return $this->_normalizePalettePayload($payload);
    }

    public function getEditorConfigForGroup(FormGroup $group): array
    {
        $palette = $this->_getGroupPaletteForEditor($group);
        $fieldMeta = $this->_getFieldMetaByClass();
        $editorPalette = $this->_preparePaletteForEditor($palette, $fieldMeta);

        return [
            'payloadInputId' => 'formie-field-palette-payload',
            'canEdit' => Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
            'palette' => $editorPalette,
        ];
    }

    public function getSavePayloadForGroup(FormGroup $group): array
    {
        $palette = $this->_getGroupPaletteForEditor($group);
        $fieldMeta = $this->_getFieldMetaByClass();

        return $this->_serializePaletteForSave(
            $this->_preparePaletteForEditor($palette, $fieldMeta),
        );
    }

    public function saveGroupPalette(FormGroup $group, array $payload): bool
    {
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            Formie::error('Group field palette cannot be saved when allowAdminChanges is disabled.', __METHOD__);

            return false;
        }

        $normalized = $this->_normalizePalettePayload($payload);

        if ($normalized === null) {
            return false;
        }

        $settings = $group->getSettingsModel();
        $settings->fieldPalette = $normalized;

        if (!$settings->validate()) {
            return false;
        }

        $group->setSettingsModel($settings);

        return Formie::$plugin->getFormGroups()->saveGroup($group, false);
    }

    public function saveDefaultPalette(string $message = ''): bool
    {
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            return false;
        }

        $palette = $this->_buildDefaultPalette();

        Craft::$app->getProjectConfig()->set(
            self::CONFIG_KEY,
            $palette,
            $message ?: Craft::t('formie', 'Install default field palette'),
        );

        $this->_resolvedPalette = null;

        return true;
    }

    public function getResolvedPalette(?Form $form = null): array
    {
        if ($form) {
            $groupPalette = Formie::$plugin->getFormGroupPolicy()->getFieldPaletteConfig($form->getGroup());

            if ($groupPalette !== null) {
                return $this->_mergeRegistryFields($groupPalette);
            }
        }

        if ($this->_resolvedPalette !== null) {
            return $this->_resolvedPalette;
        }

        if ($this->_isResolvingPalette) {
            return $this->_getLegacyFallbackPalette();
        }

        $this->_isResolvingPalette = true;

        try {
            $stored = $this->_getStoredConfig();
            $palette = is_array($stored) ? $stored : $this->_buildDefaultPalette();

            return $this->_resolvedPalette = $this->_mergeRegistryFields($palette);
        } finally {
            $this->_isResolvingPalette = false;
        }
    }

    public function isFieldClassEnabled(string $fieldClass): bool
    {
        if ($fieldClass === MissingField::class) {
            return true;
        }

        if ($this->_resolvedPalette === null && $this->_isResolvingPalette) {
            return $this->_isFieldClassEnabledInLegacySettings($fieldClass);
        }

        foreach ($this->_iteratePaletteEntries($this->getResolvedPalette()) as $entry) {
            if (($entry['fieldClass'] ?? null) === $fieldClass) {
                return (bool)($entry['enabled'] ?? true);
            }
        }

        return true;
    }

    public function buildFormBuilderFieldTypeGroups(array $fullConfigTypes = [], ?Form $form = null): array
    {
        $palette = $this->getResolvedPalette($form);
        $groupedFields = [];

        foreach ($palette['groups'] ?? [] as $group) {
            $fields = $this->_buildFieldConfigsForGroup($group['fields'] ?? [], $fullConfigTypes);

            if ($fields === []) {
                continue;
            }

            $groupedFields[] = [
                'label' => $group['name'] ?? $group['handle'] ?? '',
                'handle' => $group['handle'] ?? '',
                'fields' => $fields,
            ];
        }

        $unassignedFields = $this->_buildFieldConfigsForGroup($palette['unassigned'] ?? [], $fullConfigTypes);

        if ($unassignedFields !== []) {
            $groupedFields[] = [
                'label' => Craft::t('formie', 'Unassigned'),
                'handle' => self::UNASSIGNED_HANDLE,
                'fields' => $unassignedFields,
            ];
        }

        return $groupedFields;
    }


    // Private Methods
    // =========================================================================

    private function _getGroupPaletteForEditor(FormGroup $group): array
    {
        $stored = Formie::$plugin->getFormGroupPolicy()->getFieldPaletteConfig($group);

        if (is_array($stored)) {
            return $stored;
        }

        return $this->getResolvedPalette();
    }

    private function _getStoredConfig(): ?array
    {
        $config = Craft::$app->getProjectConfig()->get(self::CONFIG_KEY, true);

        return is_array($config) ? $config : null;
    }

    private function _buildDefaultPalette(): array
    {
        $disabledFields = Formie::$plugin->getSettings()->disabledFields;
        $disabledFieldSet = array_fill_keys($disabledFields, true);
        $pickableFieldClasses = $this->_getPickableFieldClasses();
        $groupedDefinitions = Formie::$plugin->getFields()->getGroupedFieldTypeDefinitions($pickableFieldClasses);

        $groups = [];
        $assignedFieldClasses = [];

        foreach ($groupedDefinitions as $groupDefinition) {
            if (($groupDefinition['handle'] ?? null) === 'internal') {
                continue;
            }

            $fields = [];

            foreach ($groupDefinition['fields'] ?? [] as $fieldDefinition) {
                $fieldClass = $fieldDefinition['type'] ?? null;

                if (!$fieldClass || !in_array($fieldClass, $pickableFieldClasses, true)) {
                    continue;
                }

                $fields[] = $this->_createPaletteEntry($fieldClass, $disabledFieldSet);
                $assignedFieldClasses[$fieldClass] = true;
            }

            if ($fields === []) {
                continue;
            }

            $handle = (string)($groupDefinition['handle'] ?? '');
            $groups[] = [
                'uid' => StringHelper::UUID(),
                'handle' => $handle,
                'name' => (string)($groupDefinition['label'] ?? $handle),
                'fields' => $fields,
            ];
        }

        return [
            'version' => self::VERSION,
            'groups' => $groups,
            'unassigned' => [],
        ];
    }

    private function _mergeRegistryFields(array $palette): array
    {
        $pickableFieldClasses = $this->_getPickableFieldClasses();
        $knownFieldClasses = [];

        foreach ($palette['groups'] ?? [] as $groupIndex => $group) {
            $palette['groups'][$groupIndex]['fields'] = $this->_sanitizePaletteEntries(
                $group['fields'] ?? [],
                $pickableFieldClasses,
                $knownFieldClasses,
            );
        }

        $palette['unassigned'] = $this->_sanitizePaletteEntries(
            $palette['unassigned'] ?? [],
            $pickableFieldClasses,
            $knownFieldClasses,
        );

        foreach ($pickableFieldClasses as $fieldClass) {
            if (isset($knownFieldClasses[$fieldClass])) {
                continue;
            }

            if ($fieldClass === CustomField::class) {
                $palette = $this->_addFieldToGroup($palette, $fieldClass, 'custom');
            } else {
                $groupHandle = $this->_getDefaultGroupHandleForFieldClass($fieldClass);

                if ($groupHandle) {
                    $palette = $this->_addFieldToGroup($palette, $fieldClass, $groupHandle);
                } else {
                    $palette['unassigned'][] = $this->_createPaletteEntry($fieldClass);
                }
            }

            $knownFieldClasses[$fieldClass] = true;
        }

        $palette = $this->_relocateUnassignedFieldsToDefaultGroups($palette);

        $palette = $this->_fixOutOfOrderRegistryFields($palette);

        $palette['version'] = self::VERSION;

        return $palette;
    }

    private function _getDefaultGroupHandleForFieldClass(string $fieldClass): ?string
    {
        $pickableFieldClasses = $this->_getPickableFieldClasses();

        foreach (Formie::$plugin->getFields()->getGroupedFieldTypeDefinitions($pickableFieldClasses) as $groupDefinition) {
            $handle = $groupDefinition['handle'] ?? null;

            if (!$handle || $handle === 'internal') {
                continue;
            }

            foreach ($groupDefinition['fields'] ?? [] as $fieldDefinition) {
                if (($fieldDefinition['type'] ?? null) === $fieldClass) {
                    return $handle;
                }
            }
        }

        return null;
    }

    private function _getDefaultGroupFieldOrder(string $groupHandle): array
    {
        $pickableFieldClasses = $this->_getPickableFieldClasses();

        foreach (Formie::$plugin->getFields()->getGroupedFieldTypeDefinitions($pickableFieldClasses) as $groupDefinition) {
            if (($groupDefinition['handle'] ?? null) !== $groupHandle) {
                continue;
            }

            $order = [];

            foreach ($groupDefinition['fields'] ?? [] as $fieldDefinition) {
                $fieldClass = $fieldDefinition['type'] ?? null;

                if (is_string($fieldClass)) {
                    $order[] = $fieldClass;
                }
            }

            return $order;
        }

        return [];
    }

    private function _getDefaultInsertIndex(string $groupHandle, string $fieldClass, array $existingEntries): ?int
    {
        $defaultOrder = $this->_getDefaultGroupFieldOrder($groupHandle);

        if ($defaultOrder === []) {
            return null;
        }

        $targetIndex = array_search($fieldClass, $defaultOrder, true);

        if ($targetIndex === false) {
            return null;
        }

        $insertIndex = 0;

        foreach ($existingEntries as $existingEntry) {
            $existingClass = $existingEntry['fieldClass'] ?? null;

            if (!is_string($existingClass)) {
                continue;
            }

            $existingDefaultIndex = array_search($existingClass, $defaultOrder, true);

            if ($existingDefaultIndex !== false && $existingDefaultIndex < $targetIndex) {
                $insertIndex++;
            }
        }

        return $insertIndex;
    }

    private function _fixOutOfOrderRegistryFields(array $palette): array
    {
        foreach ($palette['groups'] ?? [] as $groupIndex => $group) {
            $handle = $group['handle'] ?? null;

            if (!is_string($handle)) {
                continue;
            }

            $orderMap = array_flip($this->_getDefaultGroupFieldOrder($handle));

            if ($orderMap === []) {
                continue;
            }

            $fields = $group['fields'] ?? [];
            $changed = true;

            while ($changed) {
                $changed = false;

                for ($index = 0, $count = count($fields); $index < $count - 1; $index++) {
                    $leftClass = $fields[$index]['fieldClass'] ?? null;
                    $rightClass = $fields[$index + 1]['fieldClass'] ?? null;

                    if (!is_string($leftClass) || !is_string($rightClass)) {
                        continue;
                    }

                    $leftOrder = $orderMap[$leftClass] ?? null;
                    $rightOrder = $orderMap[$rightClass] ?? null;

                    if ($leftOrder === null || $rightOrder === null || $leftOrder <= $rightOrder) {
                        continue;
                    }

                    $entry = $fields[$index + 1];
                    array_splice($fields, $index + 1, 1);
                    $insertIndex = $this->_getDefaultInsertIndex($handle, $rightClass, $fields);

                    if ($insertIndex === null) {
                        $fields[] = $entry;
                    } else {
                        array_splice($fields, $insertIndex, 0, [$entry]);
                    }

                    $changed = true;
                    break;
                }
            }

            $palette['groups'][$groupIndex]['fields'] = $fields;
        }

        return $palette;
    }

    private function _relocateUnassignedFieldsToDefaultGroups(array $palette): array
    {
        $remaining = [];

        foreach ($palette['unassigned'] ?? [] as $entry) {
            $fieldClass = $entry['fieldClass'] ?? null;

            if (!is_string($fieldClass)) {
                continue;
            }

            $groupHandle = $this->_getDefaultGroupHandleForFieldClass($fieldClass);

            if (!$groupHandle) {
                $remaining[] = $entry;
                continue;
            }

            $palette = $this->_addFieldEntryToGroup($palette, $groupHandle, $entry);
        }

        $palette['unassigned'] = $remaining;

        return $palette;
    }

    private function _addFieldToGroup(array $palette, string $fieldClass, string $groupHandle): array
    {
        return $this->_addFieldEntryToGroup($palette, $groupHandle, $this->_createPaletteEntry($fieldClass));
    }

    private function _addFieldEntryToGroup(array $palette, string $groupHandle, array $entry): array
    {
        $fieldClass = $entry['fieldClass'] ?? null;

        if (!is_string($fieldClass)) {
            return $palette;
        }

        foreach ($palette['groups'] ?? [] as $groupIndex => $group) {
            if (($group['handle'] ?? null) !== $groupHandle) {
                continue;
            }

            foreach ($group['fields'] ?? [] as $existingEntry) {
                if (($existingEntry['fieldClass'] ?? null) === $fieldClass) {
                    return $palette;
                }
            }

            $fields = $group['fields'] ?? [];
            $insertIndex = $this->_getDefaultInsertIndex($groupHandle, $fieldClass, $fields);

            if ($insertIndex === null) {
                $fields[] = $entry;
            } else {
                array_splice($fields, $insertIndex, 0, [$entry]);
            }

            $palette['groups'][$groupIndex]['fields'] = $fields;

            return $palette;
        }

        $groupMeta = $this->_getGroupDefinitionMeta($groupHandle);

        $palette['groups'][] = [
            'uid' => StringHelper::UUID(),
            'handle' => $groupHandle,
            'name' => $groupMeta['label'] ?? $groupHandle,
            'fields' => [$entry],
        ];

        return $palette;
    }

    private function _getGroupDefinitionMeta(string $groupHandle): array
    {
        $pickableFieldClasses = $this->_getPickableFieldClasses();

        foreach (Formie::$plugin->getFields()->getGroupedFieldTypeDefinitions($pickableFieldClasses) as $groupDefinition) {
            if (($groupDefinition['handle'] ?? null) === $groupHandle) {
                return $groupDefinition;
            }
        }

        return [];
    }

    private function _addFieldToCustomGroup(array $palette, string $fieldClass): array
    {
        return $this->_addFieldToGroup($palette, $fieldClass, 'custom');
    }

    private function _sanitizePaletteEntries(array $entries, array $pickableFieldClasses, array &$knownFieldClasses): array
    {
        $sanitized = [];

        foreach ($entries as $entry) {
            $fieldClass = $entry['fieldClass'] ?? null;

            if (!is_string($fieldClass) || !in_array($fieldClass, $pickableFieldClasses, true)) {
                continue;
            }

            if (isset($knownFieldClasses[$fieldClass])) {
                continue;
            }

            $sanitized[] = [
                'fieldClass' => $fieldClass,
                'enabled' => (bool)($entry['enabled'] ?? true),
                'label' => $this->_normalizeLabelOverride($entry['label'] ?? null),
            ];
            $knownFieldClasses[$fieldClass] = true;
        }

        return $sanitized;
    }

    private function _createPaletteEntry(string $fieldClass, array $disabledFieldSet = []): array
    {
        return [
            'fieldClass' => $fieldClass,
            'enabled' => !isset($disabledFieldSet[$fieldClass]),
            'label' => null,
        ];
    }

    private function _normalizePalettePayload(array $payload): ?array
    {
        if (!isset($payload['groups']) || !is_array($payload['groups'])) {
            Formie::error('Field palette payload is missing groups.', __METHOD__);

            return null;
        }

        $pickableFieldClasses = array_fill_keys($this->_getPickableFieldClasses(), true);
        $knownFieldClasses = [];
        $usedHandles = [];
        $groups = [];

        foreach ($payload['groups'] as $group) {
            if (!is_array($group)) {
                continue;
            }

            $name = trim((string)($group['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $handle = trim((string)($group['handle'] ?? ''));

            if ($handle === '') {
                $handle = StringHelper::toHandle($name);
            }

            if (!$this->_isValidHandle($handle) || isset($usedHandles[$handle])) {
                Formie::error('Field palette group handle is invalid or duplicated.', __METHOD__);

                return null;
            }

            $uid = trim((string)($group['uid'] ?? ''));

            if ($uid === '') {
                $uid = StringHelper::UUID();
            }

            $fields = $this->_sanitizeIncomingEntries($group['fields'] ?? [], $pickableFieldClasses, $knownFieldClasses);

            $groups[] = [
                'uid' => $uid,
                'handle' => $handle,
                'name' => $name,
                'fields' => $fields,
            ];
            $usedHandles[$handle] = true;
        }

        $unassigned = $this->_sanitizeIncomingEntries($payload['unassigned'] ?? [], $pickableFieldClasses, $knownFieldClasses);

        foreach (array_keys($pickableFieldClasses) as $fieldClass) {
            if (isset($knownFieldClasses[$fieldClass])) {
                continue;
            }

            $unassigned[] = $this->_createPaletteEntry($fieldClass);
            $knownFieldClasses[$fieldClass] = true;
        }

        return [
            'version' => self::VERSION,
            'groups' => $groups,
            'unassigned' => $unassigned,
        ];
    }

    private function _sanitizeIncomingEntries(array $entries, array $pickableFieldClasses, array &$knownFieldClasses): array
    {
        $sanitized = [];

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $fieldClass = $entry['fieldClass'] ?? null;

            if (!is_string($fieldClass) || !isset($pickableFieldClasses[$fieldClass]) || isset($knownFieldClasses[$fieldClass])) {
                continue;
            }

            $sanitized[] = [
                'fieldClass' => $fieldClass,
                'enabled' => (bool)($entry['enabled'] ?? true),
                'label' => $this->_normalizeLabelOverride($entry['label'] ?? null),
            ];
            $knownFieldClasses[$fieldClass] = true;
        }

        return $sanitized;
    }

    private function _normalizeLabelOverride(mixed $label): ?string
    {
        if (!is_string($label)) {
            return null;
        }

        $label = trim($label);

        return $label === '' ? null : $label;
    }

    private function _isValidHandle(string $handle): bool
    {
        if ($handle === '' || $handle === self::UNASSIGNED_HANDLE) {
            return false;
        }

        $validator = new HandleValidator([
            'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title'],
        ]);

        return $validator->validate($handle);
    }

    private function _getPickableFieldClasses(): array
    {
        $fieldClasses = [];

        foreach (Formie::$plugin->getFields()->getResolvedRegisteredFieldTypes(false) as $fieldClass) {
            if ($fieldClass === MissingField::class) {
                continue;
            }

            $definition = Formie::$plugin->getFields()->getFieldTypeDefinition($fieldClass);

            if (($definition['isPickable'] ?? true) === false) {
                continue;
            }

            $fieldClasses[] = $fieldClass;
        }

        return $fieldClasses;
    }

    private function _getFieldMetaByClass(): array
    {
        $meta = [];

        foreach ($this->_getPickableFieldClasses() as $fieldClass) {
            $definition = Formie::$plugin->getFields()->getFieldTypeDefinition($fieldClass);
            $meta[$fieldClass] = [
                'fieldClass' => $fieldClass,
                'defaultLabel' => (string)($definition['label'] ?? $fieldClass),
                'icon' => $definition['icon'] ?? null,
                'type' => $definition['type'] ?? $fieldClass,
            ];
        }

        return $meta;
    }

    private function _getLegacyFallbackPalette(): array
    {
        return [
            'version' => self::VERSION,
            'groups' => [],
            'unassigned' => array_map(
                fn(string $fieldClass) => $this->_createPaletteEntry(
                    $fieldClass,
                    array_fill_keys(Formie::$plugin->getSettings()->disabledFields, true),
                ),
                $this->_getPickableFieldClasses(),
            ),
        ];
    }

    private function _isFieldClassEnabledInLegacySettings(string $fieldClass): bool
    {
        return !in_array($fieldClass, Formie::$plugin->getSettings()->disabledFields, true);
    }

    private function _preparePaletteForEditor(array $palette, array $fieldMeta): array
    {
        $prepareEntries = function(array $entries) use ($fieldMeta): array {
            $prepared = [];

            foreach ($entries as $entry) {
                $fieldClass = $entry['fieldClass'] ?? null;
                $meta = $fieldMeta[$fieldClass] ?? null;

                if (!$meta) {
                    continue;
                }

                $prepared[] = array_merge($meta, [
                    'enabled' => (bool)($entry['enabled'] ?? true),
                    'label' => $entry['label'] ?? null,
                ]);
            }

            return $prepared;
        };

        $groups = [];

        foreach ($palette['groups'] ?? [] as $group) {
            $groups[] = [
                'uid' => $group['uid'] ?? StringHelper::UUID(),
                'handle' => $group['handle'] ?? '',
                'name' => $group['name'] ?? '',
                'fields' => $prepareEntries($group['fields'] ?? []),
            ];
        }

        return [
            'groups' => $groups,
            'unassigned' => $prepareEntries($palette['unassigned'] ?? []),
        ];
    }

    private function _serializePaletteForSave(array $palette): array
    {
        $serializeFields = function(array $fields): array {
            $serialized = [];

            foreach ($fields as $field) {
                $label = trim((string)($field['label'] ?? ''));

                $serialized[] = [
                    'fieldClass' => $field['fieldClass'] ?? null,
                    'enabled' => ($field['enabled'] ?? true) !== false,
                    'label' => $label !== '' ? $label : null,
                ];
            }

            return $serialized;
        };

        $groups = [];

        foreach ($palette['groups'] ?? [] as $group) {
            $groups[] = [
                'uid' => $group['uid'] ?? null,
                'handle' => $group['handle'] ?? '',
                'name' => $group['name'] ?? '',
                'fields' => $serializeFields($group['fields'] ?? []),
            ];
        }

        return [
            'groups' => $groups,
            'unassigned' => $serializeFields($palette['unassigned'] ?? []),
        ];
    }

    private function _buildFieldConfigsForGroup(array $entries, array $fullConfigTypes): array
    {
        $fields = [];

        foreach ($entries as $entry) {
            if (($entry['enabled'] ?? true) === false) {
                continue;
            }

            $fieldClass = $entry['fieldClass'] ?? null;

            if (!$fieldClass) {
                continue;
            }

            $field = Formie::$plugin->getFields()->getRegisteredFieldByType($fieldClass, false);

            if (!$field || $field instanceof MissingField) {
                continue;
            }

            $fieldConfig = $field->getFieldTypeConfig(true);
            $labelOverride = $this->_normalizeLabelOverride($entry['label'] ?? null);

            if ($labelOverride !== null) {
                $fieldConfig['label'] = $labelOverride;
            }

            if ($fullConfigTypes !== [] && !in_array($fieldClass, $fullConfigTypes, true)) {
                continue;
            }

            $fields[] = $fieldConfig;
        }

        return $fields;
    }

    private function _iteratePaletteEntries(array $palette): \Generator
    {
        foreach ($palette['groups'] ?? [] as $group) {
            foreach ($group['fields'] ?? [] as $entry) {
                yield $entry;
            }
        }

        foreach ($palette['unassigned'] ?? [] as $entry) {
            yield $entry;
        }
    }
}
