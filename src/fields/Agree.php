<?php
namespace verbb\formie\fields;

use verbb\formie\base\Field;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\PreviewableFieldInterface;
use verbb\formie\base\SortableFieldInterface;
use verbb\formie\fields\definitions\FieldReferenceValue;
use verbb\formie\fields\values\BooleanFieldValue;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\SlotTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\RichText;
use verbb\formie\positions\Hidden as HiddenPosition;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Template;

use yii\db\Schema;

use GraphQL\Type\Definition\Type;

use Twig\Markup;

class Agree extends Field implements SortableFieldInterface, PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Agree');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/agree/icon.svg';
    }

    public static function dbType(): string
    {
        return Schema::TYPE_BOOLEAN;
    }


    // Properties
    // =========================================================================

    public RichText $description;
    public ?string $checkedValue = null;
    public ?string $uncheckedValue = null;

    // Private due to parsing done at render-time, not before
    private ?string $_descriptionHtml = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Setuo defaults for some values which can't in in the property definition
        $config['defaultValue'] = $config['defaultValue'] ?? false;
        $config['labelPosition'] = $config['labelPosition'] ?? HiddenPosition::class;
        $config['checkedValue'] = $config['checkedValue'] ?? Craft::t('app', 'Yes');
        $config['uncheckedValue'] = $config['uncheckedValue'] ?? Craft::t('app', 'No');
        $config['description'] = RichText::from($config['description'] ?? null);

        parent::__construct($config);
    }

    public function fieldKind(): string
    {
        return self::KIND_BOOLEAN;
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        if (is_array($values)) {
            $hasDescription = array_key_exists('description', $values);

            if ($hasDescription) {
                $values['description'] = RichText::from($values['description']);
            }

            if ($hasDescription) {
                $this->_descriptionHtml = null;
            }
        }

        parent::setAttributes($values, $safeOnly);
    }

    public function attributes(): array
    {
        $names = parent::attributes();
        
        // Define `descriptionHtml` as an extra attribute, rather than a property.
        // It's not a public property to ensure it's not saved to the field settings. 
        // Without this, `setDescriptionHtml()` would not be called, and this settings could not be manipulated
        // from the front-end `setFieldSettings()`. We also cannot set this value in `init()` due to when containing a Link mark
        // `parseRefTags()` causes an infinite loop.
        $names[] = 'descriptionHtml';

        return $names;
    }

    public function hasReferenceBlockPlaceholder(): bool
    {
        return false;
    }

    public function isValueEmpty(mixed $value, ?ElementInterface $element): bool
    {
        // Use the default "empty" checks, but `false` is also considered empty here
        return parent::isValueEmpty($value, $element) || $value === false;
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        // Allow null value to represent proper empty state
        return ($value === null) ? null : (bool)$value;
    }

    public function getDescriptionHtml(): Markup
    {
        // Allow the HTML to be overridden in templates with `setFieldSettings()`.
        if (!$this->_descriptionHtml) {
            $this->_descriptionHtml = $this->_getHtmlContent($this->description);
        }

        return Template::raw(Craft::t('formie', (string)$this->_descriptionHtml));
    }

    public function getSettings(): array
    {
        $settings = parent::getSettings();
        $settings['description'] = $this->description->getSchema();

        return $settings;
    }

    public function setDescriptionHtml($value): void
    {
        $this->_descriptionHtml = $value;
    }

    public function getDefaultState(): ?string
    {
        // An alias for `defaultValue` for GQL, as `defaultValue` returns a boolean, not string
        return $this->defaultValue;
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewAgree(),
        ];
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'checkedValue' => [
                'name' => 'checkedValue',
                'type' => Type::string(),
            ],
            'uncheckedValue' => [
                'name' => 'uncheckedValue',
                'type' => Type::string(),
            ],
            // We're forced to use a string-representation of the default value, due to the parent `defaultValue` definition
            // So cast it properly here as a string, but also provide `defaultState` as the proper type.
            'defaultValue' => [
                'name' => 'defaultValue',
                'type' => Type::string(),
                'resolve' => function($field) {
                    return (string)$field->defaultValue;
                },
            ],
            'defaultState' => [
                'name' => 'defaultState',
                'type' => Type::boolean(),
            ],
            'descriptionHtml' => [
                'name' => 'descriptionHtml',
                'type' => Type::string(),
            ],
        ]);
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::richTextField(array_merge([
                'label' => Craft::t('formie', 'Description'),
                'instructions' => Craft::t('formie', 'The description for the field. This will be shown next to the checkbox.'),
                'name' => 'description',
                'validation' => 'requiredRichText',
                'required' => true,
            ], RichTextHelper::getRichTextConfig('fields.agree'))),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Checked Value'),
                'instructions' => Craft::t('formie', 'The value of this field when it is checked.'),
                'name' => 'checkedValue',
                'validation' => 'required',
                'required' => true,
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Unchecked Value'),
                'instructions' => Craft::t('formie', 'The value of this field when it is unchecked.'),
                'name' => 'uncheckedValue',
                'validation' => 'required',
                'required' => true,
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Default Value'),
                'instructions' => Craft::t('formie', 'The default value for the field when it loads.'),
                'name' => 'defaultValue',
            ]),
        ];
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
        ];
    }

    public function defineFormBuilderAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::inputAttributesField(),
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
        return ['defaultValue'];
    }

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;
        $id = $this->getHtmlId($form);
        $labelPosition = is_object($this->labelPosition) ? get_class($this->labelPosition) : (string)$this->labelPosition;
        $labelPosition = strtolower($labelPosition);
        $resolvedLabelPosition = str_contains($labelPosition, 'left') ? 'left' : (str_contains($labelPosition, 'right') ? 'right' : (str_contains($labelPosition, 'hidden') ? 'hidden' : 'above'));
        $isHiddenLabel = $context->get('labelPosition') instanceof HiddenPosition || $resolvedLabelPosition === 'hidden';

        if ($key === 'fieldLayout') {
            return SlotTag::make('fieldset')
                ->core([
                    'data-formie-field-layout' => true,
                    'data-formie-agree-field-layout' => true,
                    'data-formie-label-position' => $resolvedLabelPosition,
                    'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
                ])
                ->theme([
                    'class' => [
                        'formie-field-layout',
                        'formie-agree-field-layout',
                        "formie-field-layout-label-{$resolvedLabelPosition}",
                    ],
                ]);
        }

        if ($key === 'fieldLabel') {
            return SlotTag::make('legend')
                ->core([
                    'data-formie-label' => true,
                    'data-formie-field-label' => true,
                    'data-formie-agree-field-label' => true,
                    'data-formie-label-position' => $resolvedLabelPosition,
                    'data-formie-sr-only' => $isHiddenLabel ? true : false,
                ])
                ->theme([
                    'class' => [
                        'formie-label',
                        'formie-field-label',
                        'formie-agree-field-label',
                        $isHiddenLabel ? 'formie-sr-only' : false,
                    ],
                ]);
        }

        if ($key === 'fieldOptions') {
            return SlotTag::make('div')
                ->core([
                    'data-formie-field-options' => true,
                    'data-formie-agree-options' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-field-options',
                        'formie-agree-options',
                    ],
                ]);
        }

        if ($key === 'fieldOption') {
            return SlotTag::make('div')
                ->core([
                    'data-formie-field-option' => true,
                    'data-formie-checkbox-option' => true,
                    'data-formie-agree-option' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-field-option',
                        'formie-checkbox-option',
                        'formie-agree-option',
                    ],
                ]);
        }

        if ($key === 'fieldInput') {
            return SlotTag::make('input')
                ->core(array_merge([
                    'type' => 'checkbox',
                    'id' => $id,
                    'name' => $this->getHtmlName(),
                    'required' => $this->required ? true : null,
                    'data-formie-input' => true,
                    'data-formie-checkbox-input' => true,
                    'data-formie-agree-input' => true,
                    'data-formie-input-id' => $this->getHtmlDataId($form),
                    'data-formie-input-type' => 'agree',
                ], ValidationMessagesHelper::requiredClientAttributes($this)))
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-checkbox-input',
                        'formie-agree-input',
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }

        if ($key === 'fieldOptionLabel') {
            return SlotTag::make('label')
                ->core([
                    'data-formie-field-option-label' => true,
                    'data-formie-checkbox-option-label' => true,
                    'data-formie-agree-option-label' => true,
                    'for' => $id,
                ])
                ->theme([
                    'class' => [
                        'formie-field-option-label',
                        'formie-checkbox-option-label',
                        'formie-agree-option-label',
                    ],
                ]);
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/agree/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        return ($value) ? $this->checkedValue : $this->uncheckedValue;
    }

    protected function defineValueForIntegration(mixed $value, IntegrationField $integrationField, IntegrationInterface $integration, ElementInterface $element = null, string $fieldKey = ''): mixed
    {
        // If we require a boolean, return that
        if ($integrationField->getType() === IntegrationField::TYPE_BOOLEAN) {
            return (bool)$value;
        }

        // Fetch the default handling
        return parent::defineValueForIntegration($value, $integrationField, $integration, $element);
    }

    protected function defineClientInput(): array
    {
        return array_merge(parent::defineClientInput(), [
            'checkedValue' => $this->checkedValue,
            'uncheckedValue' => $this->uncheckedValue,
            'descriptionHtml' => (string)$this->getDescriptionHtml(),
        ]);
    }

    protected function defineReferenceValues(): array
    {
        return [
            FieldReferenceValue::default([
                'variableTypes' => [Variables::TYPE_BOOLEAN],
            ]),
        ];
    }

    protected function defineValueClass(): ?string
    {
        return BooleanFieldValue::class;
    }


    // Private Methods
    // =========================================================================

    private function _getHtmlContent(RichText $content): ?string
    {
        if ($content->isEmpty()) {
            return null;
        }

        return $content->toHtml();
    }
}
