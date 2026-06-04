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


    // Public Methods
    // =========================================================================

    public function getEditorConfig(): array
    {
        $settings = Formie::$plugin->getSettings();
        $fieldTypes = $this->getFieldTypeDefaultsConfig();

        return array_merge([
            'saveAction' => 'formie/settings/save-settings',
            'redirect' => 'formie/settings/defaults',
            'values' => $this->getEditorValues($settings, $fieldTypes),
            'options' => [
                'formTemplates' => $this->_formTemplateOptions(),
                'stencils' => $this->_stencilOptions(),
                'emailTemplates' => $this->_emailTemplateOptions(),
                'statuses' => $this->_statusOptions(),
                'labelPositions' => Formie::$plugin->getFields()->getLabelPositionsOptions(),
                'instructionsPositions' => Formie::$plugin->getFields()->getInstructionsPositionsOptions(),
                'dataRetentionOptions' => $this->_dataRetentionOptions(),
                'submitMethodOptions' => $this->_submitMethodOptions(),
                'fileUploadsActionOptions' => $this->_fileUploadsActionOptions(),
                'progressCalculationOptions' => $this->_progressCalculationOptions(),
                'progressPositionOptions' => $this->_progressPositionOptions(),
                'requiredIndicatorOptions' => $this->_requiredIndicatorOptions(),
            ],
            'fieldTypes' => $fieldTypes,
            'initialFieldType' => $fieldTypes[0]['type'] ?? null,
            'submissionTitleFormatVariableConfig' => $this->getSubmissionTitleFormatVariableConfig(),
        ], Variables::getFormBuilderVariableConfig());
    }

    public function getEditorValues(Settings $settings): array
    {
        return [
            'defaultFormTemplate' => $settings->defaultFormTemplate,
            'defaultFormStencil' => $settings->defaultFormStencil,
            'defaultEmailTemplate' => $settings->defaultEmailTemplate,
            'defaultLabelPosition' => $settings->defaultLabelPosition,
            'defaultInstructionsPosition' => $settings->defaultInstructionsPosition,
            'formDefaults' => $settings->getNormalizedFormDefaults(),
            'fieldDefaults' => $this->getAllFieldDefaultsValues($settings),
            'notificationDefaults' => $settings->getNormalizedNotificationDefaults(),
        ];
    }

    public function getAllFieldDefaultsValues(Settings $settings): array
    {
        $values = is_array($settings->fieldDefaults ?? null) ? $settings->fieldDefaults : [];

        foreach ($this->getFieldTypeDefaultsConfig() as $fieldType) {
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

        foreach ([
            'fromName',
            'from',
            'replyTo',
            'replyToName',
            'subject',
            'attachFiles',
            'attachPdf',
            'enabled',
        ] as $name) {
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
        return [
            'submissionTitleFormat',
            'collectIp',
            'collectUser',
            'submitMethod',
            'displayFormTitle',
            'displayCurrentPageTitle',
            'displayPageTabs',
            'displayPageProgress',
            'progressCalculation',
            'progressPosition',
            'scrollToTop',
            'requiredIndicator',
        ];
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

    private function _statusOptions(): array
    {
        $options = [
            ['label' => Craft::t('formie', 'System default status'), 'value' => ''],
        ];

        foreach (Formie::$plugin->getStatuses()->getAllStatuses() as $status) {
            $options[] = [
                'label' => $status->name,
                'value' => $status->handle,
                'status' => $status->color,
            ];
        }

        return $options;
    }

    private function _dataRetentionOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Forever'), 'value' => 'forever'],
            ['label' => Craft::t('formie', 'Number of minutes'), 'value' => 'minutes'],
            ['label' => Craft::t('formie', 'Number of hours'), 'value' => 'hours'],
            ['label' => Craft::t('formie', 'Number of days'), 'value' => 'days'],
            ['label' => Craft::t('formie', 'Number of weeks'), 'value' => 'weeks'],
            ['label' => Craft::t('formie', 'Number of months'), 'value' => 'months'],
            ['label' => Craft::t('formie', 'Number of years'), 'value' => 'years'],
        ];
    }

    private function _submitMethodOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Page Reload (Server-side)'), 'value' => 'page-reload'],
            ['label' => Craft::t('formie', 'Ajax (Client-side)'), 'value' => 'ajax'],
        ];
    }

    private function _fileUploadsActionOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Retain files'), 'value' => 'retain'],
            ['label' => Craft::t('formie', 'Delete files'), 'value' => 'delete'],
        ];
    }

    private function _progressCalculationOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Completion'), 'value' => 'completion'],
            ['label' => Craft::t('formie', 'Page position'), 'value' => 'page-position'],
        ];
    }

    private function _progressPositionOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Start of form'), 'value' => 'start'],
            ['label' => Craft::t('formie', 'End of form'), 'value' => 'end'],
        ];
    }

    private function _requiredIndicatorOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Asterisk for required fields'), 'value' => 'asterisk'],
            ['label' => Craft::t('formie', 'Optional indicator for non-required fields'), 'value' => 'optional'],
        ];
    }
}
