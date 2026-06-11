<?php
namespace verbb\formie\fields;

use verbb\formie\base\FieldInterface;
use verbb\formie\base\OptionsField;
use verbb\formie\base\SortableFieldInterface;
use verbb\formie\fields\values\SingleOptionFieldValue;
use verbb\formie\fields\definitions\FieldClientModules;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\ClientModule;
use verbb\formie\models\SlotTag;
use verbb\formie\positions\Hidden as HiddenPosition;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;

use Faker\Generator as FakerFactory;

class Radio extends OptionsField implements SortableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Radio Buttons');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/radio/icon.svg';
    }

    public static function phpType(): string
    {
        return sprintf('\\%s', SingleOptionFieldValue::class);
    }

    public function themeConfigKey(): string
    {
        return 'radioButtons';
    }


    // Properties
    // =========================================================================

    public ?string $layout = 'vertical';


    // Public Methods
    // =========================================================================

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewChoiceList('radio'),
        ];
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            ...$this->defineOptionDynamicGeneralSchema(),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Static Options'),
                'instructions' => Craft::t('formie', 'Add, remove, or reorder option rows manually.'),
                'name' => 'options',
                'if' => 'optionsMode == "static"',
                'enableOptionRowMenu' => true,
                'enableBulkOptions' => true,
                'predefinedOptions' => $this->getPredefinedOptions(),
                'newRowDefaults' => [
                    'default' => false,
                ],
                'columns' => [
                    [
                        'type' => 'text',
                        'name' => 'label',
                        'label' => Craft::t('formie', 'Option Label'),
                        'required' => true,
                    ],
                    [
                        'type' => 'value',
                        'name' => 'value',
                        'label' => Craft::t('formie', 'Value'),
                        'source' => 'label',
                    ],
                    [
                        'type' => 'radio',
                        'name' => 'default',
                        'label' => Craft::t('formie', 'Default'),
                        'allowUnselect' => true,
                    ],
                ],
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::prePopulate(),
            SchemaHelper::includeInEmailFieldSummariesField(),
            SchemaHelper::emailFieldSummaryValue([
                'options' => [
                    ['label' => Craft::t('formie', 'Label'), 'value' => 'label'],
                    ['label' => Craft::t('formie', 'Value'), 'value' => 'value'],
                ],
            ]),
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
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Layout'),
                'instructions' => Craft::t('formie', 'Select which layout to use for these fields.'),
                'name' => 'layout',
                'options' => [
                    ['label' => Craft::t('formie', 'Vertical'), 'value' => 'vertical'],
                    ['label' => Craft::t('formie', 'Horizontal'), 'value' => 'horizontal'],
                ],
            ]),
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
            SchemaHelper::inputAttributesField([
                'instructions' => Craft::t('formie', 'Add attributes to be outputted on this field’s input. Note that these attributes will be added to every radio option.'),
            ]),
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
        return ['layout'];
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
                    'data-formie-radio-field-layout' => true,
                    'data-formie-layout' => $this->layout ?? 'vertical',
                    'data-formie-label-position' => $resolvedLabelPosition,
                    'aria-describedby' => $this->hasInstructions() ? "{$id}-instructions" : null,
                ])
                ->theme([
                    'class' => [
                        'formie-field-layout',
                        'formie-radio-field-layout',
                        'formie-layout-' . ($this->layout ?? 'vertical'),
                        "formie-field-layout-label-{$resolvedLabelPosition}",
                    ],
                ]);
        }

        if ($key === 'fieldLabel') {
            return SlotTag::make('legend')
                ->core([
                    'data-formie-label' => true,
                    'data-formie-field-label' => true,
                    'data-formie-radio-field-label' => true,
                    'data-formie-label-position' => $resolvedLabelPosition,
                    'data-formie-sr-only' => $isHiddenLabel ? true : false,
                ])
                ->theme([
                    'class' => [
                        'formie-label',
                        'formie-field-label',
                        'formie-radio-field-label',
                        $isHiddenLabel ? 'formie-sr-only' : false,
                    ],
                ]);
        }

        if ($key === 'fieldOptions') {
            return SlotTag::make('div')
                ->core([
                    'data-formie-field-options' => true,
                    'data-formie-radio-options' => true,
                    'data-formie-layout' => $this->layout ?? 'vertical',
                ])
                ->theme([
                    'class' => [
                        'formie-field-options',
                        'formie-radio-options',
                        'formie-layout-' . ($this->layout ?? 'vertical'),
                    ],
                ]);
        }

        if ($key === 'fieldOption') {
            return SlotTag::make('div')
                ->core([
                    'data-formie-field-option' => true,
                    'data-formie-radio-option' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-field-option',
                        'formie-radio-option',
                    ],
                ]);
        }

        if ($key === 'fieldInput') {
            $optionValue = $this->getFieldInputOptionValue($context->toArray());

            return SlotTag::make('input')
                ->core(array_merge([
                    'type' => 'radio',
                    'id' => $this->getHtmlId($form, $optionValue),
                    'name' => $this->getHtmlName(($this->hasMultiNamespace ? '[]' : null)),
                    'required' => $this->required ? true : null,
                    'data-formie-input' => true,
                    'data-formie-radio-input' => true,
                    'data-formie-input-id' => $this->getHtmlDataId($form, $optionValue),
                    'data-formie-input-type' => 'radio',
                ], ValidationMessagesHelper::requiredClientAttributes($this)))
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-radio-input',
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }

        if ($key === 'fieldOptionLabel') {
            $optionValue = $this->getFieldInputOptionValue($context->toArray());

            return SlotTag::make('label')
                ->core([
                    'data-formie-field-option-label' => true,
                    'data-formie-radio-option-label' => true,
                    'for' => $this->getHtmlId($form, $optionValue),
                ])
                ->theme([
                    'class' => [
                        'formie-field-option-label',
                        'formie-radio-option-label',
                    ],
                ]);
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/radio/input', [
            'name' => $this->handle,
            'value' => $value,
            'options' => $this->translatedOptions(),
        ]);
    }

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        return $faker->randomElement($this->options)['value'] ?? '';
    }

    protected function optionsSettingLabel(): string
    {
        return Craft::t('app', 'Radio Button Options');
    }

    protected function defineClientModules(): array
    {
        $modules = parent::defineClientModules();
        $modules[] = new ClientModule([
            'id' => 'checkbox-radio',
            'renderTargets' => [ClientModule::RENDER_TARGET_FRONTEND],
        ]);

        return $modules;
    }

    protected function definePrimaryOptionVariableSourceTypes(): array
    {
        return [Variables::TYPE_TEXT];
    }
}
