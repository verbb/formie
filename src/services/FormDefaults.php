<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\fields\Date;
use verbb\formie\fields\FileUpload;
use verbb\formie\fields\MissingField;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\Notification;
use verbb\formie\models\Settings;

use Craft;
use craft\helpers\DateTimeHelper;

use yii\base\Component;

class FormDefaults extends Component
{
    // Properties
    // =========================================================================

    private ?array $_fieldTypeDefaultsConfig = null;
    private ?array $_formDefaultsSchemaConfig = null;
    private ?array $_notificationDefaultsSchemaConfig = null;


    // Public Methods
    // =========================================================================

    public function getEditorConfig(): array
    {
        $settings = Formie::$plugin->getSettings();
        $fieldTypes = $this->getFieldTypeDefaultsConfig();
        $formDefaultsSchema = $this->getFormDefaultsSchemaConfig();
        $notificationDefaultsSchema = $this->getNotificationDefaultsSchemaConfig();

        return array_merge([
            'saveAction' => 'formie/settings/save-settings',
            'redirect' => 'formie/settings/defaults',
            'values' => $this->getEditorValues($settings, $fieldTypes),
            'options' => [
                'formTemplates' => $this->_formTemplateOptions(),
                'stencils' => $this->_stencilOptions(),
                'emailTemplates' => $this->_emailTemplateOptions(),
                'labelPositions' => Formie::$plugin->getFields()->getLabelPositionsOptions(),
                'instructionsPositions' => Formie::$plugin->getFields()->getInstructionsPositionsOptions(),
                'integrationCaptchas' => $this->getIntegrationCaptchaOptions(),
            ],
            'formDefaultsSchema' => $formDefaultsSchema['schema'],
            'formDefaultsSchemaIndex' => $formDefaultsSchema,
            'notificationDefaultsSchema' => $notificationDefaultsSchema['schema'],
            'notificationDefaultsSchemaIndex' => $notificationDefaultsSchema,
            'fieldTypes' => $fieldTypes,
            'initialFieldType' => $fieldTypes[0]['type'] ?? null,
            'submissionTitleFormatVariableConfig' => $this->getSubmissionTitleFormatVariableConfig(),
        ], Variables::getFormBuilderVariableConfig());
    }

    public function normalizeSettingsPayload(array $settings): array
    {
        $settings = $this->migrateLegacyFieldDefaults($settings);

        if (isset($settings['notificationDefaults']) && is_array($settings['notificationDefaults'])) {
            $settings['notificationDefaults'] = $this->normalizeNotificationDefaultsForStorage($settings['notificationDefaults']);
        }

        if (isset($settings['integrationDefaults']['captchas']) && is_array($settings['integrationDefaults']['captchas'])) {
            $settings['integrationDefaults']['captchas'] = $this->normalizeIntegrationCaptchaDefaultsForStorage(
                $settings['integrationDefaults']['captchas'],
            );
        }

        return $settings;
    }

    public function getEditorValues(Settings $settings, ?array $fieldTypes = null): array
    {
        return [
            'defaultFormTemplate' => $settings->defaultFormTemplate,
            'defaultFormStencil' => $settings->defaultFormStencil,
            'defaultEmailTemplate' => $settings->defaultEmailTemplate,
            'defaultLabelPosition' => $settings->defaultLabelPosition,
            'defaultInstructionsPosition' => $settings->defaultInstructionsPosition,
            'formDefaults' => $settings->getNormalizedFormDefaults(),
            'fieldDefaults' => $this->getAllFieldDefaultsValues($settings, $fieldTypes),
            'notificationDefaults' => $this->prepareNotificationDefaultsForEditor($settings->getNormalizedNotificationDefaults()),
            'integrationDefaults' => $this->prepareIntegrationDefaultsForEditor($settings->getNormalizedIntegrationDefaults()),
        ];
    }

    public function getAllFieldDefaultsValues(Settings $settings, ?array $fieldTypes = null): array
    {
        $values = is_array($settings->fieldDefaults ?? null) ? $settings->fieldDefaults : [];

        foreach ($fieldTypes ?? $this->getFieldTypeDefaultsConfig() as $fieldType) {
            $type = $fieldType['type'];

            if (!isset($values[$type]) || !is_array($values[$type])) {
                $values[$type] = $this->resolveFieldTypeDefaults($type);
            } else {
                $values[$type] = array_replace($this->resolveFieldTypeDefaults($type), $values[$type]);
            }
        }

        return $values;
    }

    public function resolveFieldTypeDefaults(string $fieldClass): array
    {
        $settings = Formie::$plugin->getSettings();
        $stored = $settings->fieldDefaults[$fieldClass] ?? [];

        if (!is_array($stored)) {
            return [];
        }

        return $stored;
    }

    public function getFieldTypeDefaultsConfig(): array
    {
        if ($this->_fieldTypeDefaultsConfig !== null) {
            return $this->_fieldTypeDefaultsConfig;
        }

        $fieldTypes = [];

        foreach (Formie::$plugin->getFields()->getRegisteredFields(false) as $field) {
            if ($field instanceof MissingField) {
                continue;
            }

            $schema = $field->getDefaultableSettingsSchema();

            if ($schema === []) {
                continue;
            }

            $compiledSchema = SchemaHelper::compileSchema(SchemaHelper::schemaNode($schema));

            $fieldTypes[] = [
                'type' => get_class($field),
                'label' => $field::displayName(),
                'schema' => $compiledSchema['schema'],
                'schemaIndex' => $compiledSchema,
            ];
        }

        usort($fieldTypes, static function(array $a, array $b) {
            return strcasecmp($a['label'], $b['label']);
        });

        return $this->_fieldTypeDefaultsConfig = $fieldTypes;
    }

    public function getFormDefaultsSchemaConfig(): array
    {
        if ($this->_formDefaultsSchemaConfig !== null) {
            return $this->_formDefaultsSchemaConfig;
        }

        $schema = (new Form())->getDefaultableSettingsSchema();
        $compiledSchema = SchemaHelper::compileSchema(SchemaHelper::schemaNode($schema));

        return $this->_formDefaultsSchemaConfig = $compiledSchema;
    }

    public function getNotificationDefaultsSchemaConfig(): array
    {
        if ($this->_notificationDefaultsSchemaConfig !== null) {
            return $this->_notificationDefaultsSchemaConfig;
        }

        $schema = Formie::$plugin->getNotifications()->getDefaultableSettingsSchema();
        $compiledSchema = SchemaHelper::compileSchema(SchemaHelper::schemaNode($schema));

        return $this->_notificationDefaultsSchemaConfig = $compiledSchema;
    }

    public function getIntegrationCaptchaOptions(): array
    {
        $options = [];

        foreach (Formie::$plugin->getIntegrations()->getAllCaptchas() as $captcha) {
            if (!$captcha->hasFormSettings()) {
                continue;
            }

            $options[] = [
                'handle' => $captcha->handle,
                'label' => $captcha->getName(),
            ];
        }

        return $options;
    }

    public function applyCaptchaDefaultsToNewForm(Form $form): void
    {
        $settings = Formie::$plugin->getSettings();
        $captchaDefaults = $settings->getNormalizedIntegrationDefaults()['captchas'] ?? [];

        foreach (Formie::$plugin->getIntegrations()->getAllCaptchas() as $captcha) {
            if (!$captcha->hasFormSettings()) {
                continue;
            }

            $handle = $captcha->handle;
            $default = array_key_exists($handle, $captchaDefaults) ? $captchaDefaults[$handle] : null;

            if ($this->_shouldInheritDefaultValue($default)) {
                if ($captcha->getEnabled()) {
                    $form->settings->integrations[$handle]['enabled'] = true;
                }

                continue;
            }

            $form->settings->integrations[$handle]['enabled'] = (bool)$default;
        }
    }

    public function normalizeNotificationDefaultsForStorage(array $defaults): array
    {
        foreach (Formie::$plugin->getNotifications()->supportedNotificationDefaults() as $name) {
            if (!array_key_exists($name, $defaults)) {
                continue;
            }

            if (!in_array($name, ['attachFiles', 'attachPdf', 'enabled'], true)) {
                continue;
            }

            $defaults[$name] = $this->_normalizeInheritBooleanValue($defaults[$name]);
        }

        return $defaults;
    }

    public function prepareNotificationDefaultsForEditor(array $defaults): array
    {
        foreach (['attachFiles', 'attachPdf', 'enabled'] as $name) {
            if (!array_key_exists($name, $defaults)) {
                continue;
            }

            $defaults[$name] = $this->_formatInheritBooleanForEditor($defaults[$name]);
        }

        return $defaults;
    }

    public function prepareIntegrationDefaultsForEditor(array $defaults): array
    {
        $captchas = $defaults['captchas'] ?? [];

        if (!is_array($captchas)) {
            $captchas = [];
        }

        foreach ($this->getIntegrationCaptchaOptions() as $captcha) {
            $handle = $captcha['handle'];

            if (!array_key_exists($handle, $captchas)) {
                $captchas[$handle] = '';
            } else {
                $captchas[$handle] = $this->_formatInheritBooleanForEditor($captchas[$handle]);
            }
        }

        $defaults['captchas'] = $captchas;

        return $defaults;
    }

    public function normalizeIntegrationCaptchaDefaultsForStorage(array $captchas): array
    {
        $normalized = [];

        foreach ($captchas as $handle => $value) {
            $normalized[$handle] = $this->_normalizeInheritBooleanValue($value);
        }

        return $normalized;
    }

    public function applyToNewForm(Form $form, array $postedValues = []): void
    {
        $settings = Formie::$plugin->getSettings();
        $defaults = $settings->getNormalizedFormDefaults();

        if (!array_key_exists('defaultStatusId', $postedValues) && ($defaults['defaultStatus'] ?? '')) {
            $status = Formie::$plugin->getStatuses()->getStatusByHandle((string)$defaults['defaultStatus']);
            $form->defaultStatusId = $status?->id;
        }

        if (!array_key_exists('dataRetention', $postedValues)) {
            $form->dataRetention = (string)($defaults['dataRetention'] ?? 'forever');
        }

        if (!array_key_exists('dataRetentionValue', $postedValues)) {
            $form->dataRetentionValue = $defaults['dataRetentionValue'] ?: null;
        }

        if (!array_key_exists('fileUploadsAction', $postedValues)) {
            $form->fileUploadsAction = (string)($defaults['fileUploadsAction'] ?? 'retain');
        }

        $postedSettings = $postedValues['settings'] ?? [];

        if (!is_array($postedSettings)) {
            $postedSettings = [];
        }

        foreach ($this->_formSettingsDefaultKeys() as $name) {
            $this->_applyFormSettingDefault($form, $postedSettings, $name, $defaults[$name] ?? null);
        }
    }

    public function applyToNewField(array &$config, string $fieldClass, ?array $supported = null): void
    {
        $defaults = $this->resolveFieldTypeDefaults($fieldClass);

        if ($supported === null) {
            $supported = $this->_getSupportedDefaults($fieldClass);
        }

        if ($defaults === [] || $supported === []) {
            return;
        }

        foreach ($supported as $key) {
            if (!array_key_exists($key, $defaults)) {
                continue;
            }

            $value = $defaults[$key];

            if ($this->_shouldInheritDefaultValue($value)) {
                continue;
            }

            if (!array_key_exists($key, $config)) {
                $config[$key] = $this->normalizeFieldDefaultValue($fieldClass, $key, $value);
            }
        }
    }

    public function applyToNewNotification(Notification $notification, array $postedValues = []): void
    {
        $settings = Formie::$plugin->getSettings();
        $defaults = $settings->getNormalizedNotificationDefaults();

        if (!$notification->templateId && empty($postedValues['templateId'])) {
            $templateId = $settings->getDefaultEmailTemplateId();

            if ($templateId) {
                $notification->templateId = $templateId;
            }
        }

        foreach (Formie::$plugin->getNotifications()->supportedNotificationDefaults() as $name) {
            if (array_key_exists($name, $postedValues)) {
                continue;
            }

            $value = $defaults[$name] ?? null;

            if ($this->_shouldInheritDefaultValue($value)) {
                continue;
            }

            $notification->{$name} = $value;
        }
    }

    public function normalizeFieldDefaultValue(string $fieldClass, string $key, mixed $value): mixed
    {
        if ($fieldClass === Date::class && $key === 'defaultValue' && is_string($value)) {
            return DateTimeHelper::toDateTime($value, false, false) ?: $value;
        }

        return $value;
    }

    public function migrateLegacyFieldDefaults(array $settings): array
    {
        $fieldDefaults = $settings['fieldDefaults'] ?? [];

        if (!is_array($fieldDefaults)) {
            $fieldDefaults = [];
        }

        $fileUploadDefaults = $fieldDefaults[FileUpload::class] ?? [];

        if (!is_array($fileUploadDefaults)) {
            $fileUploadDefaults = [];
        }

        if ($fileUploadDefaults === [] && !empty($settings['defaultFileUploadVolume'])) {
            $fieldDefaults[FileUpload::class] = array_replace($fileUploadDefaults, [
                'uploadLocationSource' => (string)$settings['defaultFileUploadVolume'],
            ]);
        }

        $dateDefaults = $fieldDefaults[Date::class] ?? [];

        if (!is_array($dateDefaults)) {
            $dateDefaults = [];
        }

        if ($dateDefaults === []) {
            $legacyDateDefaults = [];

            if (!empty($settings['defaultDateDisplayType'])) {
                $legacyDateDefaults['displayType'] = (string)$settings['defaultDateDisplayType'];
            }

            if (array_key_exists('defaultDateValueOption', $settings)) {
                $legacyDateDefaults['defaultOption'] = (string)$settings['defaultDateValueOption'];
            }

            if (!empty($settings['defaultDateTime'])) {
                $legacyDateDefaults['defaultValue'] = DateTimeHelper::toDateTime($settings['defaultDateTime'])?->format('Y-m-d H:i:s');
            }

            if ($legacyDateDefaults !== []) {
                $fieldDefaults[Date::class] = array_replace($dateDefaults, $legacyDateDefaults);
            }
        }

        $settings['fieldDefaults'] = $fieldDefaults;

        unset(
            $settings['defaultFileUploadVolume'],
            $settings['defaultDateDisplayType'],
            $settings['defaultDateValueOption'],
            $settings['defaultDateTime'],
        );

        return $settings;
    }

    public function applyDefaultStencil(Form $form): bool
    {
        $settings = Formie::$plugin->getSettings();
        $stencilHandle = trim((string)$settings->defaultFormStencil);

        if ($stencilHandle === '') {
            return false;
        }

        $stencil = Formie::$plugin->getStencils()->getStencilByHandle($stencilHandle);

        if (!$stencil) {
            return false;
        }

        $stencil->applyStencilToForm($form, true);

        return true;
    }

    public function getSubmissionTitleFormatVariableConfig(): array
    {
        return [
            'content' => Variables::CONTENT_SINGLE_LINE,
            'types' => [Variables::TYPE_TEXT],
            'groups' => [
                Variables::STATIC_FORM,
                Variables::STATIC_GENERAL,
                Variables::STATIC_SITE,
            ],
        ];
    }


    // Private Methods
    // =========================================================================

    private function _formSettingsDefaultKeys(): array
    {
        return array_values(array_diff(Form::supportedFormDefaults(), [
            'defaultStatus',
            'dataRetention',
            'dataRetentionValue',
            'fileUploadsAction',
        ]));
    }

    private function _applyFormSettingDefault(Form $form, array $postedSettings, string $name, mixed $value): void
    {
        if (array_key_exists($name, $postedSettings) || $this->_shouldInheritDefaultValue($value)) {
            return;
        }

        $form->settings->{$name} = $value;
    }

    private function _shouldInheritDefaultValue(mixed $value): bool
    {
        return $value === null || $value === '';
    }

    private function _normalizeInheritBooleanValue(mixed $value): mixed
    {
        if ($this->_shouldInheritDefaultValue($value)) {
            return null;
        }

        if ($value === '1' || $value === 1 || $value === true) {
            return true;
        }

        if ($value === '0' || $value === 0 || $value === false) {
            return false;
        }

        return $value;
    }

    private function _formatInheritBooleanForEditor(mixed $value): string
    {
        if ($this->_shouldInheritDefaultValue($value)) {
            return '';
        }

        return $value ? '1' : '0';
    }

    private function _getSupportedDefaults(string $fieldClass): array
    {
        foreach (Formie::$plugin->getFields()->getRegisteredFields(false) as $field) {
            if ($field instanceof MissingField) {
                continue;
            }

            if (get_class($field) === $fieldClass) {
                return $field->getSupportedDefaults();
            }
        }

        return [];
    }

    private function _formTemplateOptions(): array
    {
        $options = [
            ['label' => Craft::t('formie', 'Default Formie Template'), 'value' => ''],
        ];

        foreach (Formie::$plugin->getFormTemplates()->getAllTemplates() as $template) {
            $options[] = ['label' => $template->name, 'value' => $template->handle];
        }

        return $options;
    }

    private function _stencilOptions(): array
    {
        $options = [
            ['label' => Craft::t('formie', 'No default stencil'), 'value' => ''],
        ];

        foreach (Formie::$plugin->getStencils()->getAllStencils() as $stencil) {
            $options[] = ['label' => $stencil->name, 'value' => $stencil->handle];
        }

        return $options;
    }

    private function _emailTemplateOptions(): array
    {
        $options = [
            ['label' => Craft::t('formie', 'Default Formie Template'), 'value' => ''],
        ];

        foreach (Formie::$plugin->getEmailTemplates()->getAllTemplates() as $template) {
            $options[] = ['label' => $template->name, 'value' => $template->handle];
        }

        return $options;
    }
}
