<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\PreviewableFieldInterface;
use verbb\formie\base\SortableFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFieldValueEvent;
use verbb\formie\fields\definitions\FieldReferenceValue;
use verbb\formie\fields\custom\CustomFieldAdapterInterface;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Json;

use GraphQL\Type\Definition\Type;

use yii\db\Schema;

class CustomField extends Field implements SortableFieldInterface, PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Custom Field');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/custom-field/icon.svg';
    }

    public static function getInputTemplatePath(): string
    {
        return 'fields/custom-field';
    }

    public static function dbType(): string
    {
        // Adapters can be scalar or structured; JSON gives the single Formie
        // field type room to support both without per-adapter content columns.
        return Schema::TYPE_JSON;
    }


    // Properties
    // =========================================================================

    public ?string $customFieldAdapter = null;
    public ?string $customFieldAdapterHandle = null;
    public array $customFieldAdapterSettings = [];


    // Public Methods
    // =========================================================================

    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'customFieldAdapter';
        $attributes[] = 'customFieldAdapterSettings';

        return $attributes;
    }

    public function fieldKind(): string
    {
        return self::KIND_CUSTOM;
    }

    public function getAdapter(): CustomFieldAdapterInterface
    {
        return Formie::$plugin->getCustomFields()->createAdapter($this->customFieldAdapter);
    }

    public function getDefaultValue(): mixed
    {
        $defaultValue = $this->normalizeValue($this->getAdapter()->getDefaultValue($this), null);

        $event = new ModifyFieldValueEvent([
            'value' => $defaultValue,
            'field' => $this,
        ]);

        $this->trigger(static::EVENT_MODIFY_DEFAULT_VALUE, $event);

        return is_string($event->value) ? trim($event->value) : $event->value;
    }

    public function getCustomFieldAdapterSettings(): array
    {
        return is_array($this->customFieldAdapterSettings) ? $this->customFieldAdapterSettings : [];
    }

    public function getCustomFieldAdapterSetting(string $key, mixed $default = null): mixed
    {
        $settings = $this->getCustomFieldAdapterSettings();

        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        $value = parent::normalizeValue($value, $element);

        return $this->getAdapter()->normalizeValue($value, $this, $element);
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        $value = $this->getAdapter()->serializeValue($value, $this, $element);

        return parent::serializeValue($value, $element);
    }

    public function isValueEmpty(mixed $value, ?ElementInterface $element): bool
    {
        return $this->getAdapter()->isValueEmpty($value, $this, $element);
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();
        $rules[] = [$this->handle, 'validateCustomFieldValue', 'skipOnEmpty' => false];

        return $rules;
    }

    public function validateCustomFieldValue(ElementInterface $element): void
    {
        $this->getAdapter()->validateValue($element, $this);
    }

    public function getFieldTypeConfigData(): array
    {
        return array_merge(parent::getFieldTypeConfigData(), [
            'customFieldAdapters' => Formie::$plugin->getCustomFields()->getAdapterDefinitions(),
        ]);
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return $this->getAdapter()->getFormBuilderPreviewSchema($this);
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'customFieldAdapter' => [
                'name' => 'customFieldAdapter',
                'type' => Type::string(),
            ],
            'customFieldAdapterSettings' => [
                'name' => 'customFieldAdapterSettings',
                'type' => Type::string(),
                'resolve' => static function(self $field): string {
                    return Json::encode($field->getCustomFieldAdapterSettings());
                },
            ],
        ], $this->getAdapter()->getSettingGqlTypes($this));
    }

    public function getContentGqlType(): Type|array
    {
        return $this->getAdapter()->getContentGqlType($this);
    }

    public function getContentGqlMutationArgumentType(): Type|array
    {
        return $this->getAdapter()->getContentGqlMutationArgumentType($this);
    }

    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        return $this->getAdapter()->getPreviewHtml($this, $value, $element);
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        $schema = [
            SchemaHelper::labelField(),
        ];

        foreach (Formie::$plugin->getCustomFields()->getAdapterTypes() as $adapterType) {
            $adapter = Formie::$plugin->getCustomFields()->createAdapter($adapterType);

            foreach ($adapter->getFormBuilderSettingsSchema($this) as $node) {
                $adapterCondition = 'customFieldAdapterHandle == "' . $adapterType::handle() . '"';
                $nodeCondition = trim((string)($node['if'] ?? ''));
                $node['if'] = $nodeCondition ? "($adapterCondition) && ($nodeCondition)" : $adapterCondition;
                $schema[] = $node;
            }
        }

        return $schema;
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::prePopulate(),
            SchemaHelper::includeInEmailFieldSummariesField(),
        ];
    }

    public function defineFormBuilderValidationSchema(): array
    {
        return [
            SchemaHelper::requiredField(),
            SchemaHelper::requiredValidationMessage(),
        ];
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
            SchemaHelper::errorMessagePosition($this),
        ];
    }

    public function defineFormBuilderAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::inputAttributesField(),
            SchemaHelper::enableContentEncryptionField(),
        ];
    }

    public function defineFormBuilderConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    public function getAdapterInputHtml(Form $form, mixed $value): string
    {
        return $this->getAdapter()->getInputHtml($this, $form, $value);
    }


    // Protected Methods
    // =========================================================================

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->getAdapter()->getCpInputHtml($this, $value, $element, $inline);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['customFieldAdapter'], 'required'];
        $rules[] = [['customFieldAdapterSettings'], 'safe'];
        $rules[] = [['customFieldAdapter'], 'in', 'range' => array_map(
            static fn(string $adapterType): string => $adapterType,
            Formie::$plugin->getCustomFields()->getAdapterTypes(),
        )];

        return $rules;
    }

    protected function defineValueClass(): ?string
    {
        return $this->getAdapter()->getValueClass($this);
    }

    protected function defineClientInput(): array
    {
        return array_merge(parent::defineClientInput(), [
            'customFieldAdapter' => $this->customFieldAdapter,
            'customFieldAdapterSettings' => $this->getCustomFieldAdapterSettings(),
        ], $this->getAdapter()->getClientInput($this));
    }

    protected function defineClientModules(): array
    {
        return array_merge(parent::defineClientModules(), $this->getAdapter()->getClientModules($this));
    }

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        return $this->getAdapter()->getValueAsString($value, $this, $element);
    }

    protected function defineValueAsArray(mixed $value, ElementInterface $element = null): mixed
    {
        return $this->getAdapter()->getValueAsArray($value, $this, $element);
    }

    protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed
    {
        return $this->getAdapter()->getValueForExport($value, $this, $element);
    }

    protected function defineValueForIntegration(mixed $value, IntegrationField $integrationField, IntegrationInterface $integration, ElementInterface $element = null, string $fieldKey = ''): mixed
    {
        return $this->getAdapter()->getValueForIntegration($value, $this, $integrationField, $integration, $element, $fieldKey);
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): mixed
    {
        return $this->getAdapter()->getValueForSummary($value, $this, $element);
    }

    protected function defineValueForReference(mixed $value, ElementInterface $element = null): mixed
    {
        return $this->getAdapter()->getValueForReference($value, $this, $element);
    }

    protected function defineValueForReferenceBlock(mixed $value, Notification $notification, ElementInterface $element = null): mixed
    {
        return $this->getAdapter()->getValueForReferenceBlock($value, $this, $notification, $element);
    }

    protected function defineValueForCondition(mixed $value, Submission $submission): mixed
    {
        return $this->getAdapter()->getValueForCondition($value, $this, $submission);
    }

    protected function defineReferenceValues(): array
    {
        return [
            FieldReferenceValue::default([
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
        ];
    }
}
