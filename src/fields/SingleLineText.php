<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\PreviewableFieldInterface;
use verbb\formie\base\SortableFieldInterface;
use verbb\formie\fields\coercion\StringValueCoercer;
use verbb\formie\fields\definitions\FieldReferenceValue;
use verbb\formie\fields\values\StringFieldValue;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\fields\conditions\TextFieldConditionRule;
use verbb\formie\fields\traits\AutocompleteFieldTrait;
use verbb\formie\fields\traits\TextLimitFieldTrait;
use verbb\formie\fields\traits\UniqueValueFieldTrait;
use verbb\formie\elements\Submission;
use verbb\formie\models\ClientModule;
use verbb\formie\models\SlotTag;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;

class SingleLineText extends Field implements SortableFieldInterface, PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Single-line Text');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/single-line-text/icon.svg';
    }

    public static function supportsGqlConfigProvider(): bool
    {
        return true;
    }

    public static function compatibleFieldTypes(): array
    {
        return [
            Email::class,
        ];
    }

    
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_UNIQUE_QUERY = 'modifyUniqueQuery';


    // Traits
    // =========================================================================

    use AutocompleteFieldTrait;
    use TextLimitFieldTrait;
    use UniqueValueFieldTrait;


    // Public Methods
    // =========================================================================

    public function fieldKind(): string
    {
        return self::KIND_TEXT;
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        $value = $value !== '' ? $value : null;

        return parent::normalizeValue($value, $element);
    }

    public function getElementConditionRuleType(): ?string
    {
        return TextFieldConditionRule::class;
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        foreach ($this->getTextLimitElementValidationRules() as $rule) {
            $rules[] = $rule;
        }

        foreach ($this->getUniqueValueElementValidationRules() as $rule) {
            $rules[] = $rule;
        }

        return $rules;
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewInput(),
        ];
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(
            parent::getSettingGqlTypes(),
            $this->defineAutocompleteGqlType(),
            $this->defineTextLimitGqlType(),
            $this->defineUniqueValueGqlType(),
        );
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'instructions' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                'name' => 'placeholder',
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Default Value'),
                'instructions' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                'name' => 'defaultValue',
                'variableConfig' => [
                    'content' => Variables::CONTENT_SINGLE_LINE,
                    'types' => [Variables::TYPE_TEXT],
                    'groups' => [
                        Variables::STATIC_FORM,
                        Variables::STATIC_GENERAL,
                        Variables::STATIC_SITE,
                    ],
                ],
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::prePopulate(),
            $this->defineAutocompleteSettingSchema(),
            SchemaHelper::includeInEmailFieldSummariesField(),
        ];
    }

    public function defineFormBuilderValidationSchema(): array
    {
        return [
            SchemaHelper::requiredField(),
            SchemaHelper::requiredValidationMessage(),
            ...$this->defineTextLimitValidationSchema(),
            SchemaHelper::matchField([
                'includedTypes' => [self::class],
            ]),
            SchemaHelper::matchValidationMessage(),
            ...$this->defineUniqueValueValidationSchema(),
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
    

    // Protected Methods
    // =========================================================================

    protected function supportedDefaults(): array
    {
        return ['autocomplete'];
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        foreach ($this->defineAutocompleteRules() as $rule) {
            $rules[] = $rule;
        }

        foreach ($this->defineTextLimitRules() as $rule) {
            $rules[] = $rule;
        }

        foreach ($this->defineUniqueValueRules() as $rule) {
            $rules[] = $rule;
        }

        return $rules;
    }

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;
        $errors = $context->errors;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($key === 'fieldInput') {
            $value = $context->get('value');

            return SlotTag::make('input')
                ->core($this->applyTextLimitInputAttributes([
                    'type' => 'text',
                    'id' => $id,
                    'name' => $this->getHtmlName(),
                    'value' => $value ?? false,
                    'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                    'required' => $this->required ? true : null,
                    'data-formie-input' => true,
                    'data-formie-single-line-text-input' => true,
                    'data-formie-input-id' => $dataId,
                    'data-formie-input-type' => 'text',
                    'data-formie-input-error-state' => $errors ? true : false,
                    'autocomplete' => $this->getAutocompleteCoreAttribute(),
                    'aria-describedby' => $this->hasInstructions() ? "{$id}-instructions" : null,
                ]))
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-single-line-text-input',
                        $errors ? 'formie-input-error' : false,
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }

        if ($textLimitTag = $this->defineTextLimitFieldSlotTag($key, $context)) {
            return $textLimitTag;
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineValueForCondition(mixed $value, Submission $submission): mixed
    {
        return StringValueCoercer::forCondition($value);
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/single-line-text/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'textLimitConfig' => $this->getTextLimitClientConfig(ClientModule::RENDER_TARGET_CP_EDIT),
        ]);
    }

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        return StringValueCoercer::asString($value);
    }

    protected function defineValueAsArray(mixed $value, ElementInterface $element = null): mixed
    {
        return StringValueCoercer::asArray($value);
    }

    protected function defineReferenceValues(): array
    {
        return [
            FieldReferenceValue::default([
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
        ];
    }

    protected function supportsPlainTextHtmlSanitization(): bool
    {
        return true;
    }

    protected function defineClientInput(): array
    {
        return array_merge(parent::defineClientInput(), $this->getTextLimitClientInput(), [
            'inputType' => 'text',
        ]);
    }

    protected function defineClientModules(): array
    {
        $modules = parent::defineClientModules();

        foreach ($this->defineTextLimitClientModules() as $module) {
            $modules[] = $module;
        }

        return $modules;
    }

    protected function defineValueClass(): ?string
    {
        return StringFieldValue::class;
    }
}
