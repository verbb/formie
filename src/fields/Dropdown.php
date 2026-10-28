<?php
namespace verbb\formie\fields;

use verbb\formie\base\ChildFieldInterface;
use verbb\formie\base\OptionsField;
use verbb\formie\base\SortableFieldInterface;
use verbb\formie\fields\traits\AutocompleteFieldTrait;
use verbb\formie\fields\traits\OptionsLimitFieldTrait;
use verbb\formie\fields\traits\SearchableDropdownFieldTrait;
use verbb\formie\helpers\FieldBuilderPolicy;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\models\SlotTag;
use verbb\formie\references\ReferenceType;
use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Json;

use Faker\Generator as FakerFactory;

class Dropdown extends OptionsField implements SortableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Dropdown');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/dropdown/icon.svg';
    }


    // Traits
    // =========================================================================

    use AutocompleteFieldTrait;
    use OptionsLimitFieldTrait;
    use SearchableDropdownFieldTrait {
        getSearchableDropdownBrowserModuleConfig as protected getBaseSearchableDropdownBrowserModuleConfig;
    }


    // Properties
    // =========================================================================

    public bool $optgroups = true;
    public bool $showPlaceholder = false;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        $this->normalizeOptionsLimitConstructorConfig($config);

        parent::__construct($config);

        // Compound fields own their subfield prompts and authoring controls.
        if ($this instanceof ChildFieldInterface) {
            return;
        }

        // Older fields have no switch. Preserve explicitly configured prompts,
        // while leaving their option rows and default selections untouched.
        if (!array_key_exists('showPlaceholder', $config)) {
            $this->showPlaceholder = (bool)$this->placeholder;
        } elseif (array_key_exists('placeholder', $config) && $config['placeholder'] === '') {
            $this->placeholder = '';
        }
    }

    public function afterCreateField(array $data): void
    {
        parent::afterCreateField($data);

        // Craft's component factory typecasts an empty nullable string to null.
        // Recover an authored blank label from the original persisted settings.
        $settings = Json::decodeIfJson($data['settings'] ?? []) ?: [];
        $config = array_merge($data, $settings);

        if (array_key_exists('showPlaceholder', $config) && array_key_exists('placeholder', $config) && $config['placeholder'] === '') {
            $this->placeholder = '';
        }
    }

    public function getDefaultOptions(): array
    {
        return $this instanceof ChildFieldInterface ? [
            ['label' => Craft::t('formie', 'Select an option'), 'value' => '', 'optgroup' => false, 'default' => true],
        ] : [];
    }

    public function getPlaceholderLabel(): string
    {
        if ($this->placeholder !== null) {
            return $this->placeholder;
        }

        return Craft::t('formie', 'Select an option');
    }

    public function getFieldOptions(): array
    {
        if ($this instanceof ChildFieldInterface) {
            $options = parent::getFieldOptions();

            if ($this->placeholder) {
                array_unshift($options, ['label' => $this->placeholder, 'value' => '']);
            }

            return $options;
        }

        $options = parent::getFieldOptions();

        // An empty answer belongs to the dropdown, independently of its option source.
        // Multi-selects use prompt text in the searchable control without an empty item.
        if ($this->showPlaceholder && !$this->multi) {
            array_unshift($options, ['label' => $this->getPlaceholderLabel(), 'value' => '']);
        }

        return $options;
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        foreach ($this->getOptionsLimitElementValidationRules() as $rule) {
            $rules[] = $rule;
        }

        return $rules;
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewSelect([
                'showPlaceholderOption' => $this instanceof ChildFieldInterface ? true : SchemaHelper::previewBind('field.showPlaceholder', false),
                'managePlaceholder' => !$this instanceof ChildFieldInterface,
            ]),
        ];
    }

    public function getFieldTypeConfig(bool $includeSchemaIndex = true): array
    {
        $config = parent::getFieldTypeConfig($includeSchemaIndex);

        // Only newly inserted fields opt in; loading an existing field must not do so.
        if (!$this instanceof ChildFieldInterface) {
            $config['newField']['showPlaceholder'] = true;
        }

        return $config;
    }

    public function getFormBuilderSettings(): array
    {
        $settings = parent::getFormBuilderSettings();

        if (!$this instanceof ChildFieldInterface) {
            $settings['placeholder'] = $this->getPlaceholderLabel();

            // The preview adds the dedicated prompt itself. Preserve source rows,
            // including any manually authored empty options.
            if ($this->showPlaceholder && !$this->multi && isset($settings['_previewOptions'])) {
                array_shift($settings['_previewOptions']);
            }
        }

        foreach ($settings['options'] as &$option) {
            if (isset($option['optgroup']) && $option['optgroup']) {
                $option['label'] = $option['optgroup'];
                $option['optgroup'] = true;
            } else {
                $option['optgroup'] = false;
            }
        }

        return $settings;
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            ...$this->defineOptionDynamicGeneralSchema(),
            ...FieldBuilderPolicy::multiSelectDropdownSchema(),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Static Options'),
                'instructions' => Craft::t('formie', 'Add, remove, or reorder option rows manually.'),
                'name' => 'options',
                'if' => 'optionsMode == "static"',
                'enableOptionRowMenu' => true,
                'enableBulkOptions' => true,
                'predefinedOptions' => $this->getPredefinedOptions(),
                'newRowDefaults' => [
                    'optgroup' => false,
                    'default' => false,
                ],
                'columns' => [
                    [
                        'type' => 'checkbox',
                        'name' => 'optgroup',
                        'label' => Craft::t('formie', 'Optgroup?'),
                    ],
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
                        'type' => $this->multi ? 'checkbox' : 'radio',
                        'name' => 'default',
                        'label' => Craft::t('formie', 'Default'),
                        'allowUnselect' => !$this->multi,
                    ],
                ],
            ]),
        ];
    }

    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'useSearchable';
        $attributes[] = 'searchableOptionLimit';
        $attributes[] = 'showPlaceholder';

        return $attributes;
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), $this->defineSearchableDropdownGqlType(), $this->defineSearchableDropdownLimitGqlType());
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::prefillQueryParam(),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Show Placeholder'),
                'instructions' => Craft::t('formie', 'Show a prompt when no option is selected.'),
                'name' => 'showPlaceholder',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder Text'),
                'instructions' => Craft::t('formie', 'The prompt has an empty value and does not count as an answer.'),
                'name' => 'placeholder',
                'if' => 'showPlaceholder',
            ]),
            $this->defineSearchableDropdownSettingSchema(),
            $this->defineSearchableDropdownLimitSettingSchema(),
            $this->defineAutocompleteSettingSchema(),
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
            ...$this->defineOptionsLimitValidationSchema(['if' => 'multiple']),
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

    protected function defineSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;
        $errors = $context->errors;

        if ($key === 'fieldInput') {
            $optionValue = $this->getFieldInputOptionValue($context->toArray());

            return SlotTag::make('select')
                ->core($this->applySearchableDropdownSelectAttributes(array_merge([
                    'id' => $this->getHtmlId($form, $optionValue),
                    'name' => $this->getHtmlName(($this->multi || $this->hasMultiNamespace ? '[]' : null)),
                    'multiple' => $this->multi ? true : null,
                    'required' => $this->required ? true : null,
                    'data-formie-select' => true,
                    'data-formie-dropdown-input' => true,
                    'data-formie-input-id' => $this->getHtmlDataId($form, $optionValue),
                    'data-formie-input-type' => 'select',
                    'data-formie-input-error-state' => $errors ? true : false,
                ], ValidationMessagesHelper::requiredBrowserAttributes($this))))
                ->theme([
                    'class' => [
                        'formie-select',
                        'formie-dropdown-input',
                        $errors ? 'formie-input-error' : false,
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }

        return parent::defineSlotTag($key, $context);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = ['showPlaceholder', 'boolean'];

        foreach ($this->defineAutocompleteRules() as $rule) {
            $rules[] = $rule;
        }

        foreach ($this->defineSearchableDropdownRules() as $rule) {
            $rules[] = $rule;
        }

        foreach ($this->defineOptionsLimitRules() as $rule) {
            $rules[] = $rule;
        }

        return $rules;
    }

    protected function getSearchableDropdownBrowserModuleConfig(): array
    {
        if ($this instanceof ChildFieldInterface) {
            return $this->getBaseSearchableDropdownBrowserModuleConfig();
        }

        return [
            'multiple' => $this->multi,
            'maxOptions' => $this->searchableOptionLimit,
            'placeholder' => $this->showPlaceholder ? $this->getPlaceholderLabel() : null,
            'allowClear' => $this->showPlaceholder,
        ];
    }

    protected function defineBrowserModules(): array
    {
        $modules = parent::defineBrowserModules();

        foreach ($this->defineSearchableDropdownBrowserModules() as $module) {
            $modules[] = $module;
        }

        return $modules;
    }

    protected function defineClientRenderedInput(): array
    {
        return array_merge(parent::defineClientRenderedInput(), $this->getOptionsLimitClientRenderedInput(), [
            'autocomplete' => $this->getAutocompleteCoreAttribute(),
            'useSearchable' => $this->useSearchable,
            'searchableOptionLimit' => $this->searchableOptionLimit,
            'placeholder' => $this instanceof ChildFieldInterface ? $this->placeholder : ($this->showPlaceholder ? $this->getPlaceholderLabel() : null),
        ]);
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/dropdown/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'options' => $this->translatedOptions(),
        ]);
    }

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        $values = $faker->randomElement($this->options)['value'] ?? '';

        if ($this->multi) {
            $values = [$values];
        }

        return $values;
    }

    protected function optionsSettingLabel(): string
    {
        return Craft::t('app', 'Dropdown Options');
    }

    protected function definePrimaryOptionReferenceTypes(): array
    {
        return $this->multi
            ? [ReferenceType::Text, ReferenceType::List, ReferenceType::Boolean]
            : [ReferenceType::Text];
    }
}
