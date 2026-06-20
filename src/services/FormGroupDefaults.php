<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\FormGroup;
use verbb\formie\models\FormGroupSettings;
use verbb\formie\models\FormSitePolicy;

use Craft;
use craft\helpers\App;

use yii\base\Component;

class FormGroupDefaults extends Component
{
    // Public Methods
    // =========================================================================

    public function getEditorConfig(FormGroup $group): array
    {
        $formDefaults = Formie::$plugin->getFormDefaults();
        $groupSettings = $group->getSettingsModel();
        $baseConfig = $formDefaults->getEditorConfig();

        $fieldPalette = Formie::$plugin->getFieldPalette();

        $config = array_merge($baseConfig, [
            'payloadInputId' => 'formie-form-group-settings',
            'values' => $this->getEditorValues($group, $groupSettings),
            'statusOptions' => $this->_statusOptions(),
            'siteOptions' => Formie::$plugin->getFormSitePropagation()->getSiteOptionsForEditor(),
            'sitePropagationOptions' => $this->_sitePropagationOptions(),
            'fieldPaletteSeed' => $fieldPalette->getEditorConfig()['palette'],
            'canEdit' => Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
            'context' => 'group',
        ]);

        $config['options'] = $this->_groupOptions($config['options'] ?? []);
        $config = $this->_patchGroupEditorSchemas($config);

        return $config;
    }

    public function getEditorValues(FormGroup $group, ?FormGroupSettings $groupSettings = null): array
    {
        $groupSettings ??= $group->getSettingsModel();
        $storedDefaults = $groupSettings->defaults ?? [];
        $fieldPalette = Formie::$plugin->getFieldPalette();
        $sitePolicy = $groupSettings->getSitePolicyModel();

        $values = [
            'defaultFormStencil' => $storedDefaults['defaultFormStencil'] ?? '',
            'defaultFormTemplate' => $storedDefaults['defaultFormTemplate'] ?? '',
            'defaultEmailTemplate' => $storedDefaults['defaultEmailTemplate'] ?? '',
            'defaultLabelPosition' => $storedDefaults['defaultLabelPosition'] ?? '',
            'defaultInstructionsPosition' => $storedDefaults['defaultInstructionsPosition'] ?? '',
            'defaultErrorMessagePosition' => $storedDefaults['defaultErrorMessagePosition'] ?? '',
            'formDefaults' => $storedDefaults['formDefaults'] ?? [],
            'fieldDefaults' => $storedDefaults['fieldDefaults'] ?? [],
            'notificationDefaults' => $storedDefaults['notificationDefaults'] ?? [],
            'integrationDefaults' => $storedDefaults['integrationDefaults'] ?? [],
            'validationMessageDefaults' => $storedDefaults['validationMessageDefaults'] ?? [],
            'name' => $group->name,
            'handle' => $group->handle,
            'allowedStatusIds' => $this->_formatAllowedStatusIdsForEditor($groupSettings->allowedStatusIds),
            'useCustomFieldPalette' => $groupSettings->usesCustomFieldPalette(),
            'fieldPalette' => $groupSettings->usesCustomFieldPalette()
                ? $fieldPalette->getEditorConfigForGroup($group)['palette']
                : null,
            'sitePolicyEnabledSiteIds' => $this->_formatSiteIdsForEditor($sitePolicy->enabledSiteIds),
            'sitePolicyPropagation' => $sitePolicy->propagation,
        ];

        return $values;
    }

    public function applyPayload(FormGroup $formGroup, array $payload): bool
    {
        $settings = $formGroup->getSettingsModel();

        $formGroup->name = trim((string)($payload['name'] ?? $formGroup->name));
        $formGroup->handle = trim((string)($payload['handle'] ?? $formGroup->handle));

        $settings->allowedStatusIds = $this->_normalizeAllowedStatusIds($payload['allowedStatusIds'] ?? null);
        $settings->defaults = $this->_normalizeDefaultsPayload($payload);
        $settings->fieldPalette = $this->_normalizeFieldPalettePayload($payload);
        $settings->sitePolicy = $this->_normalizeSitePolicyPayload($payload);

        if (!$settings->validate()) {
            foreach ($settings->getErrors() as $attribute => $errors) {
                foreach ($errors as $error) {
                    $formGroup->addError($attribute, $error);
                }
            }

            return false;
        }

        $formGroup->setSettingsModel($settings);

        return true;
    }

    public function getMergedEditorValues(?FormGroup $group): array
    {
        $globalValues = Formie::$plugin->getFormDefaults()->getEditorValues(Formie::$plugin->getSettings());

        if (!$group) {
            return $globalValues;
        }

        $groupValues = $this->getEditorValues($group);
        $merged = $globalValues;

        foreach ([
            'defaultFormStencil',
            'defaultFormTemplate',
            'defaultEmailTemplate',
            'defaultLabelPosition',
            'defaultInstructionsPosition',
            'defaultErrorMessagePosition',
        ] as $key) {
            $value = $groupValues[$key] ?? '';

            if (!$this->_shouldInheritDefaultValue($value)) {
                $merged[$key] = $value;
            }
        }

        foreach (['formDefaults', 'fieldDefaults', 'notificationDefaults', 'integrationDefaults', 'validationMessageDefaults'] as $key) {
            $merged[$key] = array_replace_recursive(
                $merged[$key] ?? [],
                $this->_filterInheritedValues($groupValues[$key] ?? []),
            );
        }

        return $merged;
    }

    public function getMergedFormDefaults(?FormGroup $group): array
    {
        $global = Formie::$plugin->getSettings()->getNormalizedFormDefaults();
        $mergedValues = $this->getMergedEditorValues($group);

        return array_replace($global, $mergedValues['formDefaults'] ?? []);
    }


    // Private Methods
    // =========================================================================

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

    private function _formatAllowedStatusIdsForEditor(?array $allowedStatusIds): array|string
    {
        if ($allowedStatusIds === null) {
            return '*';
        }

        return array_map('strval', $allowedStatusIds);
    }

    private function _normalizeAllowedStatusIds(mixed $value): ?array
    {
        if ($value === null || $value === '' || $value === '*' || $value === []) {
            return null;
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        if (in_array('*', $value, true)) {
            return null;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $value))));

        return $ids === [] ? null : $ids;
    }

    private function _normalizeDefaultsPayload(array $payload): array
    {
        $defaults = [
            'defaultFormStencil' => trim((string)($payload['defaultFormStencil'] ?? '')),
            'defaultFormTemplate' => trim((string)($payload['defaultFormTemplate'] ?? '')),
            'defaultEmailTemplate' => trim((string)($payload['defaultEmailTemplate'] ?? '')),
            'defaultLabelPosition' => trim((string)($payload['defaultLabelPosition'] ?? '')),
            'defaultInstructionsPosition' => trim((string)($payload['defaultInstructionsPosition'] ?? '')),
            'defaultErrorMessagePosition' => trim((string)($payload['defaultErrorMessagePosition'] ?? '')),
            'formDefaults' => is_array($payload['formDefaults'] ?? null) ? $payload['formDefaults'] : [],
            'fieldDefaults' => is_array($payload['fieldDefaults'] ?? null) ? $payload['fieldDefaults'] : [],
            'notificationDefaults' => is_array($payload['notificationDefaults'] ?? null) ? $payload['notificationDefaults'] : [],
            'integrationDefaults' => is_array($payload['integrationDefaults'] ?? null) ? $payload['integrationDefaults'] : [],
            'validationMessageDefaults' => is_array($payload['validationMessageDefaults'] ?? null) ? $payload['validationMessageDefaults'] : [],
        ];

        return array_filter($defaults, function(mixed $value, string $key): bool {
            if (in_array($key, ['formDefaults', 'fieldDefaults', 'notificationDefaults', 'integrationDefaults', 'validationMessageDefaults'], true)) {
                return $value !== [];
            }

            return !$this->_shouldInheritDefaultValue($value);
        }, ARRAY_FILTER_USE_BOTH);
    }

    private function _normalizeFieldPalettePayload(array $payload): ?array
    {
        if (!(App::normalizeBooleanValue($payload['useCustomFieldPalette'] ?? false) ?? false)) {
            return null;
        }

        $palette = $payload['fieldPalette'] ?? null;

        if (!is_array($palette)) {
            return Formie::$plugin->getFieldPalette()->getResolvedPalette();
        }

        $normalized = Formie::$plugin->getFieldPalette()->normalizePalettePayload($palette);

        return $normalized ?? Formie::$plugin->getFieldPalette()->getResolvedPalette();
    }

    private function _patchGroupEditorSchemas(array $config): array
    {
        $inheritLabel = Craft::t('formie', 'Inherit global default');

        foreach (['formDefaultsSchema', 'notificationDefaultsSchema', 'validationMessageDefaultsSchema'] as $key) {
            if (!isset($config[$key])) {
                continue;
            }

            $config[$key] = SchemaHelper::patchGroupInheritSchema($config[$key], $inheritLabel);
        }

        foreach (['formDefaultsSchemaIndex', 'notificationDefaultsSchemaIndex', 'validationMessageDefaultsSchemaIndex'] as $key) {
            if (!isset($config[$key]['schema'])) {
                continue;
            }

            $config[$key]['schema'] = SchemaHelper::patchGroupInheritSchema($config[$key]['schema'], $inheritLabel);
        }

        if (!isset($config['fieldTypes']) || !is_array($config['fieldTypes'])) {
            return $config;
        }

        foreach ($config['fieldTypes'] as &$fieldType) {
            if (isset($fieldType['schema'])) {
                $fieldType['schema'] = SchemaHelper::patchGroupInheritSchema($fieldType['schema'], $inheritLabel);
            }

            if (isset($fieldType['schemaIndex']['schema'])) {
                $fieldType['schemaIndex']['schema'] = SchemaHelper::patchGroupInheritSchema(
                    $fieldType['schemaIndex']['schema'],
                    $inheritLabel,
                );
            }
        }
        unset($fieldType);

        return $config;
    }

    private function _groupOptions(array $options): array
    {
        $inheritLabel = Craft::t('formie', 'Inherit global default');

        foreach (['stencils', 'formTemplates', 'emailTemplates', 'labelPositions', 'instructionsPositions', 'errorMessagePositions'] as $key) {
            if (!isset($options[$key]) || !is_array($options[$key])) {
                continue;
            }

            array_unshift($options[$key], [
                'label' => $inheritLabel,
                'value' => '',
            ]);
        }

        return $options;
    }

    private function _filterInheritedValues(array $values): array
    {
        $filtered = [];

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $nested = $this->_filterInheritedValues($value);

                if ($nested !== []) {
                    $filtered[$key] = $nested;
                }

                continue;
            }

            if (!$this->_shouldInheritDefaultValue($value)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    private function _sitePropagationOptions(): array
    {
        $options = [];

        foreach (FormSitePolicy::propagationOptions() as $value => $label) {
            $options[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        return $options;
    }

    private function _formatSiteIdsForEditor(?array $enabledSiteIds): array|string
    {
        if ($enabledSiteIds === null) {
            return '*';
        }

        return array_map('strval', $enabledSiteIds);
    }

    private function _normalizeSitePolicyPayload(array $payload): array
    {
        $policy = FormSitePolicy::fromArray([
            'enabledSiteIds' => $this->_normalizeEnabledSiteIds($payload['sitePolicyEnabledSiteIds'] ?? null),
            'propagation' => $payload['sitePolicyPropagation'] ?? FormSitePolicy::PROPAGATION_ALL_ENABLED,
        ]);

        return $policy->toStorageArray();
    }

    private function _normalizeEnabledSiteIds(mixed $value): ?array
    {
        if ($value === null || $value === '' || $value === '*' || $value === []) {
            return null;
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        if (in_array('*', $value, true)) {
            return null;
        }

        $editableIds = Formie::$plugin->getFormSitePropagation()->getEditableSiteIds();
        $ids = array_values(array_unique(array_filter(array_map('intval', $value))));

        return array_values(array_intersect($ids, $editableIds)) ?: null;
    }

    private function _shouldInheritDefaultValue(mixed $value): bool
    {
        return $value === null || $value === '';
    }
}
