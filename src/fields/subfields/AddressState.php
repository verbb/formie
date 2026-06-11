<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\ChildFieldInterface;
use verbb\formie\fields\Address;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\ClientModule;
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;

use Craft;

use GraphQL\Type\Definition\Type;

class AddressState extends SingleLineText implements ChildFieldInterface
{
    // Constants
    // =========================================================================

    public const INPUT_MODE_TEXT = 'text';
    public const INPUT_MODE_DROPDOWN_WHEN_AVAILABLE = 'dropdownWhenAvailable';


    // Properties
    // =========================================================================

    public string $inputMode = self::INPUT_MODE_TEXT;
    public bool $hideWhenUnused = true;
    public bool $useSearchable = true;
    public bool $useDatalist = true;
    public string $optionLabel = 'name';
    public string $optionValue = 'name';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Address - State / Province');
    }

    public static function getInputTemplatePath(): string
    {
        return 'fields/single-line-text';
    }

    public static function getReferenceBlockTemplatePath(): string
    {
        return 'fields/single-line-text';
    }


    // Public Methods
    // =========================================================================

    public function settingsAttributes(): array
    {
        return array_merge(parent::settingsAttributes(), [
            'inputMode',
            'hideWhenUnused',
            'useSearchable',
            'useDatalist',
            'optionLabel',
            'optionValue',
        ]);
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
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Input Mode'),
                'instructions' => Craft::t('formie', 'Choose whether the state/province field should always be a text input, or use a dropdown when subdivision data is available for the selected country.'),
                'name' => 'inputMode',
                'warning' => Craft::t('formie', 'For dependent state dropdowns, place the Country sub-field before State / Province in the sub-field layout.'),
                'options' => [
                    ['label' => Craft::t('formie', 'Text'), 'value' => self::INPUT_MODE_TEXT],
                    ['label' => Craft::t('formie', 'Dropdown when available'), 'value' => self::INPUT_MODE_DROPDOWN_WHEN_AVAILABLE],
                ],
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Hide When Not Used'),
                'instructions' => Craft::t('formie', 'Hide the state/province field for countries that do not use administrative areas in their address format.'),
                'name' => 'hideWhenUnused',
                'if' => "inputMode == '" . self::INPUT_MODE_DROPDOWN_WHEN_AVAILABLE . "'",
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Use Searchable Dropdown'),
                'instructions' => Craft::t('formie', 'When subdivision data is available, enhance the state/province dropdown with type-to-filter behaviour.'),
                'name' => 'useSearchable',
                'if' => "inputMode == '" . self::INPUT_MODE_DROPDOWN_WHEN_AVAILABLE . "'",
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Use Datalist Suggestions'),
                'instructions' => Craft::t('formie', 'When subdivision data is unavailable and the field falls back to a text input, suggest known subdivisions for the selected country.'),
                'name' => 'useDatalist',
                'if' => "inputMode == '" . self::INPUT_MODE_DROPDOWN_WHEN_AVAILABLE . "'",
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Option Label'),
                'instructions' => Craft::t('formie', 'Select the format for the dropdown option label.'),
                'name' => 'optionLabel',
                'if' => "inputMode == '" . self::INPUT_MODE_DROPDOWN_WHEN_AVAILABLE . "'",
                'options' => [
                    ['label' => Craft::t('formie', 'Name'), 'value' => 'name'],
                    ['label' => Craft::t('formie', 'Short Name'), 'value' => 'short'],
                ],
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Option Value'),
                'instructions' => Craft::t('formie', 'Select the format for the dropdown option value.'),
                'name' => 'optionValue',
                'if' => "inputMode == '" . self::INPUT_MODE_DROPDOWN_WHEN_AVAILABLE . "'",
                'options' => [
                    ['label' => Craft::t('formie', 'Name'), 'value' => 'name'],
                    ['label' => Craft::t('formie', 'Short Name'), 'value' => 'short'],
                ],
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

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'inputMode' => [
                'name' => 'inputMode',
                'type' => Type::string(),
            ],
            'hideWhenUnused' => [
                'name' => 'hideWhenUnused',
                'type' => Type::boolean(),
            ],
            'useSearchable' => [
                'name' => 'useSearchable',
                'type' => Type::boolean(),
            ],
            'useDatalist' => [
                'name' => 'useDatalist',
                'type' => Type::boolean(),
            ],
            'optionLabel' => [
                'name' => 'optionLabel',
                'type' => Type::string(),
            ],
            'optionValue' => [
                'name' => 'optionValue',
                'type' => Type::string(),
            ],
        ]);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['inputMode'], 'in', 'range' => [
            self::INPUT_MODE_TEXT,
            self::INPUT_MODE_DROPDOWN_WHEN_AVAILABLE,
        ]];
        $rules[] = [['hideWhenUnused', 'useSearchable', 'useDatalist'], 'boolean'];
        $rules[] = [['optionLabel', 'optionValue'], 'in', 'range' => ['name', 'short']];

        return $rules;
    }

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;
        $errors = $context->errors;
        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);
        $value = $context->get('value');

        if ($key === 'fieldInput') {
            $attributes = $this->applyTextLimitInputAttributes([
                'type' => 'text',
                'id' => $id,
                'name' => $this->getHtmlName(),
                'value' => $value ?? false,
                'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                'required' => $this->required ? true : null,
                'autocomplete' => 'address-level1',
                'data-formie-input' => true,
                'data-formie-single-line-text-input' => true,
                'data-formie-address-state-input' => true,
                'data-formie-input-id' => $dataId,
                'data-formie-input-type' => 'text',
                'data-formie-input-error-state' => $errors ? true : false,
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ]);

            if ($this->usesDynamicSubdivisions()) {
                $attributes = array_merge($attributes, [
                    'data-formie-address-state-dynamic' => true,
                    'data-formie-address-state-hide-when-unused' => $this->hideWhenUnused ? true : null,
                    'data-formie-address-state-use-searchable' => $this->useSearchable ? true : null,
                    'data-formie-address-state-use-datalist' => $this->useDatalist ? true : null,
                    'data-formie-address-state-option-label' => $this->optionLabel,
                    'data-formie-address-state-option-value' => $this->optionValue,
                ]);
            }

            return SlotTag::make('input')
                ->core($attributes)
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-single-line-text-input',
                        'formie-address-state-input',
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

    protected function defineClientInput(): array
    {
        return array_merge(parent::defineClientInput(), [
            'inputMode' => $this->inputMode,
            'hideWhenUnused' => $this->hideWhenUnused,
            'useSearchable' => $this->useSearchable,
            'useDatalist' => $this->useDatalist,
            'optionLabel' => $this->optionLabel,
            'optionValue' => $this->optionValue,
        ]);
    }

    protected function defineClientModules(): array
    {
        $modules = parent::defineClientModules();

        if (!$this->usesDynamicSubdivisions()) {
            return $modules;
        }

        $countryOptionValue = 'short';
        $parent = $this->getParentField();

        if ($parent instanceof Address) {
            foreach ($parent->getFields(false) as $subField) {
                if ($subField instanceof AddressCountry) {
                    $countryOptionValue = $subField->optionValue ?? 'short';
                    break;
                }
            }
        }

        $modules[] = new ClientModule([
            'id' => 'address-state',
            'config' => [
                'inputMode' => $this->inputMode,
                'hideWhenUnused' => $this->hideWhenUnused,
                'useSearchable' => $this->useSearchable,
                'useDatalist' => $this->useDatalist,
                'optionLabel' => $this->optionLabel,
                'optionValue' => $this->optionValue,
                'countryOptionValue' => $countryOptionValue,
                'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                'subdivisionsAction' => 'formie/address/subdivisions',
            ],
        ]);

        return $modules;
    }


    // Private Methods
    // =========================================================================

    private function usesDynamicSubdivisions(): bool
    {
        return $this->inputMode === self::INPUT_MODE_DROPDOWN_WHEN_AVAILABLE;
    }
}
