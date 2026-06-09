<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\PreviewableFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\definitions\FieldReferenceValue;
use verbb\formie\fields\values\OptionValue;
use verbb\formie\fields\values\RecipientsFieldValue;
use verbb\formie\fields\Hidden as HiddenField;
use verbb\formie\gql\types\generators\FieldOptionGenerator;
use verbb\formie\helpers\OptionsMode;
use verbb\formie\helpers\RecipientOptionSelectionHelper;
use verbb\formie\helpers\RecipientTokenHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\OptionSource;
use verbb\formie\models\SlotTag;
use verbb\formie\models\Notification;
use verbb\formie\options\OptionResolvableInterface;
use verbb\formie\options\IntegrationOptionSourceHelper;
use verbb\formie\options\OptionSourceConfigHelper;
use verbb\formie\options\OptionSourceContext;
use verbb\formie\options\OptionSourceFieldInterface;
use verbb\formie\positions\Hidden as HiddenPosition;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Json;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\Type;

use ReflectionClass;
use ReflectionProperty;

use yii\validators\EmailValidator;

class Recipients extends Field implements PreviewableFieldInterface, OptionResolvableInterface, OptionSourceFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Recipients');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/recipients/icon.svg';
    }

    public static function supportsGqlConfigProvider(): bool
    {
        return true;
    }

    public static function gqlContentMutationArgumentTypeFromConfig(array $config): Type|array
    {
        $settings = Formie::$plugin->getFields()->getFieldConfigSettings($config);

        return ($settings['displayType'] ?? null) === 'checkboxes'
            ? Type::listOf(Type::string())
            : Type::string();
    }


    // Properties
    // =========================================================================

    public ?string $emailFieldSummaryValue = 'value';
    public string $displayType = 'hidden';
    public ?string $layout = 'vertical';
    public array $options = [];
    public string $optionsMode = OptionsMode::STATIC;
    public ?array $optionSource = null;
    public ?bool $multiple = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Setuo defaults for some values which can't in in the property definition
        $config['labelPosition'] = $config['labelPosition'] ?? HiddenPosition::class;
        $config['optionsMode'] = OptionsMode::normalize($config['optionsMode'] ?? null);
        $config['optionSource'] = OptionSourceConfigHelper::normalizeOptionSource(
            $config['optionSource'] ?? null,
            $config['optionsMode'],
            OptionSourceConfigHelper::allowedTypesForFieldClass(static::class),
        );

        if ($config['optionsMode'] === OptionsMode::DYNAMIC && $config['optionSource'] === null) {
            $config['optionsMode'] = OptionsMode::STATIC;
        }

        if (
            $config['optionsMode'] === OptionsMode::DYNAMIC
            && ($config['optionSource']['type'] ?? null) === 'integration'
            && !IntegrationOptionSourceHelper::providerSupportsUsage(
                (string)($config['optionSource']['provider'] ?? ''),
                IntegrationOptionSourceHelper::USAGE_RECIPIENTS,
            )
        ) {
            $config['optionSource'] = null;
            $config['optionsMode'] = OptionsMode::STATIC;
        }

        parent::__construct($config);
    }

    public function fieldKind(): string
    {
        return match ($this->displayType) {
            'dropdown' => self::KIND_SELECT,
            'checkboxes' => self::KIND_CHECKBOX_GROUP,
            'radio' => self::KIND_RADIO_GROUP,
            default => self::KIND_HIDDEN,
        };
    }

    public function getIsHidden(): bool
    {
        return $this->displayType === 'hidden';
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        $value = parent::normalizeValue($value, $element);

        if ($value instanceof RecipientsFieldValue) {
            return $value;
        }

        // For fields that store their content as JSON for arrays (checkboxes), convert it
        if (is_string($value) && ($value === '' || Json::isJsonObject($value))) {
            $value = Json::decodeIfJson($value);
        }

        // For non-hidden fields, ensure we cast to option field data
        if ($this->displayType !== 'hidden') {
            $selectedRecipients = RecipientOptionSelectionHelper::normalizeSelections($value);

            $options = [];
            $optionsById = [];
            $optionsByValue = [];

            foreach ($this->_getResolvedRecipientOptionRows() as $option) {
                $selected = RecipientOptionSelectionHelper::isOptionSelected($option, $selectedRecipients);
                $options[] = new OptionValue($option['label'], $option['value'], $selected, true);
                $optionsById[$option['id']] = $option;

                if (!array_key_exists($option['value'], $optionsByValue)) {
                    $optionsByValue[$option['value']] = $option;
                }
            }

            if (in_array($this->displayType, ['dropdown', 'radio'])) {
                $selection = reset($selectedRecipients) ?: null;
                $selectedValue = $selection['value'] ?? null;
                $option = RecipientOptionSelectionHelper::resolveSelectionOption($selection, $optionsById, $optionsByValue);
                $valid = $option !== null;
                $label = $selection['label'] ?? ($option['label'] ?? null);
                $value = new RecipientsFieldValue($this->displayType, $selectedValue, $label, $valid, [], $options);
            } else if ($this->displayType === 'checkboxes') {
                $selectedOptions = [];

                foreach ($selectedRecipients as $selection) {
                    $option = RecipientOptionSelectionHelper::resolveSelectionOption($selection, $optionsById, $optionsByValue);
                    $valid = $option !== null;
                    $label = $selection['label'] ?? ($option['label'] ?? null);
                    $selectedOptions[] = new OptionValue($label, $selection['value'], true, $valid);
                }

                $value = new RecipientsFieldValue($this->displayType, null, null, true, $selectedOptions, $options);
            }
        } else if ($value !== null) {
            // Ensure we're always dealing with real values. Fake values are used on front-end render.
            // Fake values will exist here if validation for the element fails.
            $value = $this->getRealValue($value);
            $value = new RecipientsFieldValue($this->displayType, $value);
        }

        return $value;
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof RecipientsFieldValue) {
            $value = match ($value->displayType()) {
                'checkboxes' => array_map(static fn(OptionValue $option): array => [
                    'value' => $option->value,
                    'label' => $option->getDisplayLabel(),
                ], $value->selectedOptions()),
                'hidden' => is_array($value->rawValue()) ? Json::encode($value->rawValue()) : $value->rawValue(),
                default => ($value->rawValue() === null || $value->rawValue() === '')
                    ? $value->rawValue()
                    : [
                        'value' => $value->rawValue(),
                        'label' => $value->label(),
                    ],
            };
        }

        return parent::serializeValue($value, $element);
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewRecipients(),
        ];
    }

    public function options(): array
    {
        return $this->options;
    }

    public function getResolvedOptions(): array
    {
        if ($this->getOptionsMode() !== OptionsMode::DYNAMIC) {
            return $this->options();
        }

        if (!Formie::$plugin) {
            return $this->options();
        }

        return Formie::$plugin->getOptionSources()->resolveRows($this, new OptionSourceContext(
            scope: Craft::$app->getRequest()->getIsCpRequest()
                ? OptionSourceContext::SCOPE_BUILDER
                : OptionSourceContext::SCOPE_RENDER,
        ));
    }

    public function getOptionsMode(): string
    {
        return OptionsMode::normalize($this->optionsMode);
    }

    public function getOptionSource(): ?OptionSource
    {
        return OptionSource::fromConfig($this->optionSource);
    }

    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'displayType';
        $attributes[] = 'layout';
        $attributes[] = 'options';
        $attributes[] = 'optionsMode';
        $attributes[] = 'optionSource';
        $attributes[] = 'multiple';

        return $attributes;
    }

    public function getFieldOptions(): array
    {
        // Do not expose email addresses in front-end HTML. Tokens are encrypted
        // typed payloads rather than row indexes so dynamic source ordering can change safely.
        $options = [];

        foreach ($this->_getResolvedRecipientOptionRows() as $key => $value) {
            $options[$key] = $value;

            if ($options[$key]['value']) {
                $options[$key]['value'] = RecipientTokenHelper::encodeOption($value, $key);
            }
        }

        return $options;
    }

    public function getFormBuilderSettings(): array
    {
        $settings = parent::getFormBuilderSettings();

        if ($this->getOptionsMode() !== OptionsMode::STATIC) {
            // Builder preview rows are preview-only; recipients must use obfuscated
            // values here just like the front-end render path.
            $previewOptions = $this->getFieldOptions();

            if ($previewOptions) {
                $settings['_previewOptions'] = $previewOptions;
            }
        }

        return $settings;
    }

    public function getClientConfig(): array
    {
        $config = parent::getClientConfig();

        if ($this->getOptionsMode() !== OptionsMode::STATIC) {
            // CP submission editing consumes the thin client config rather than
            // the full front-end payload, so expose resolved dynamic rows there too.
            $options = $this->getFieldOptions();
            $config['options'] = $options;
            $config['settings']['options'] = $options;
        }

        return $config;
    }

    public function getInputTemplateVariables(Form $form, mixed $value): array
    {
        $inputOptions = parent::getInputTemplateVariables($form, $value);

        // When rendering the value **always** swap out the real values with obscured ones
        $inputOptions['value'] = $this->getFakeValue($value);

        return $inputOptions;
    }

    public function getDisplayTypeField(): ?FieldInterface
    {
        // Use all the same settings from this field, but remove any invalid ones
        $class = new ReflectionClass($this);

        $config = [
            'options' => $this->getFieldOptions(),
        ];

        // Carry across the existing nested-field context, but preserve the namespace already applied to this field clone.
        if ($this->getParentField()) {
            // Note the order here is important for repeaters and other nested fields.
            $config['parentField'] = $this->getParentField();
        }

        $config['namespace'] = $this->getNamespace();

        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic() && $property->getDeclaringClass()->isAbstract()) {
                $config[$property->getName()] = $this->{$property->getName()};
            }
        }

        if ($this->displayType === 'hidden') {
            unset($config['options']);
            
            return new HiddenField($config);
        }

        if ($this->displayType === 'dropdown') {
            return new Dropdown($config);
        }

        if ($this->displayType === 'radio') {
            return new Radio($config);
        }

        if ($this->displayType === 'checkboxes') {
            return new Checkboxes($config);
        }

        return null;
    }

    public function getDefaultValue(): mixed
    {
        $value = parent::getDefaultValue() ?? $this->defaultValue;

        // If the default value from the parent field (query params, etc.) is empty, use the default values
        // set in the field option settings.
        if (!$this->getIsHidden() && $value === '') {
            $value = [];

            foreach ($this->getResolvedOptions() as $option) {
                if (!empty($option['default'])) {
                    $value[] = $option['value'];
                }
            }

            if ($this->displayType !== 'checkboxes') {
                $value = $value[0] ?? '';
            }
        }

        return $value;
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        if (!$this->getIsHidden()) {
            $rules[] = [$this->handle, 'validateVisibleRecipientOptions', 'skipOnEmpty' => false];
        }

        return $rules;
    }

    public function validateVisibleRecipientOptions(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->valueKey());

        if (!$value instanceof RecipientsFieldValue) {
            return;
        }

        if (in_array($this->displayType, ['dropdown', 'radio'], true) && !$value->valid()) {
            $element->addError($this->valueKey(), Craft::t('formie', 'Select a valid recipient.'));

            return;
        }

        if ($this->displayType !== 'checkboxes') {
            return;
        }

        foreach ($value->selectedOptions() as $option) {
            if ($option instanceof OptionValue && !$option->valid) {
                $element->addError($this->valueKey(), Craft::t('formie', 'Select only valid recipients.'));

                return;
            }
        }
    }

    public function getRealValue($value)
    {
        // This converts front-end-safe recipient tokens back to real addresses,
        // but still accepts real values when integrations or server-side code set them directly.

        // For any array-compatible field types (and data), recursively iterate each item
        if (is_array($value)) {
            return array_map(function($item) {
                return $this->getRealValue($item);
            }, $value);
        }

        if (!is_string($value)) {
            return $value;
        }

        // Legacy positional tokens may still be submitted by forms rendered before this request.
        if (str_contains($value, 'id:')) {
            $value = preg_replace_callback('/id:(\d+)/m', function(array $match) use ($value): string {
                $index = $match[1] ?? 0;

                return $this->getResolvedOptions()[$index]['value'] ?? $value;
            }, $value);
        }

        // Hidden recipients and visible recipient options both use encrypted values.
        if (str_starts_with($value, 'base64:')) {
            $value = RecipientTokenHelper::decode($value);

            if (is_array($value)) {
                return implode(',', array_filter($value));
            }

            // Legacy hidden recipient tokens stored JSON directly before tokens
            // became typed payloads.
            if (is_string($value) && Json::isJsonObject($value)) {
                $value = implode(',', array_filter(Json::decode($value)));
            }
        }

        return $value;
    }

    public function getFakeValue($value)
    {
        $normalized = $this->normalizeValue($value, null);

        if ($normalized instanceof RecipientsFieldValue) {
            return $normalized->toClientValue();
        }

        return $value;
    }

    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if ($value instanceof RecipientsFieldValue) {
            return $this->renderPreviewText(implode(', ', array_map(static fn(string $label): string => Craft::t('site', $label), $value->labels())));
        }

        return parent::getPreviewHtml($value, $element);
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'displayType' => [
                'name' => 'displayType',
                'type' => Type::string(),
            ],
            'multiple' => [
                'name' => 'multiple',
                'type' => Type::boolean(),
            ],
            'options' => [
                'name' => 'options',
                'type' => Type::listOf(FieldOptionGenerator::generateType()),
            ],
        ]);
    }

    public function getContentGqlMutationArgumentType(): Type|array
    {
        if ($this->displayType === 'checkboxes') {
            return Type::listOf(Type::string());
        }

        return Type::string();
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Display Type'),
                'instructions' => Craft::t('formie', 'Set different display layouts for this field.'),
                'name' => 'displayType',
                'options' => [
                    ['label' => Craft::t('formie', 'Hidden'), 'value' => 'hidden'],
                    ['label' => Craft::t('formie', 'Dropdown'), 'value' => 'dropdown'],
                    ['label' => Craft::t('formie', 'Checkboxes'), 'value' => 'checkboxes'],
                    ['label' => Craft::t('formie', 'Radio Buttons'), 'value' => 'radio'],
                ],
            ]),
            SchemaHelper::optionDynamicSettingsField([
                'fieldType' => static::class,
                'sourceTypes' => ['static', 'integration'],
                'sourceUsage' => IntegrationOptionSourceHelper::USAGE_RECIPIENTS,
                'label' => Craft::t('formie', 'Options'),
                'instructions' => Craft::t('formie', 'Define the available options for users to select from.'),
                'resolveAction' => 'formie/fields/resolve-option-source',
                'detachAction' => 'formie/fields/detach-option-source',
                'hasIntegrationOptionSources' => Formie::$plugin->getOptionSources()->hasIntegrationOptionSources(IntegrationOptionSourceHelper::USAGE_RECIPIENTS),
                'integrationConfigAction' => 'formie/fields/get-integration-option-source-config',
                'if' => 'displayType != "hidden"',
            ]),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Static Options'),
                'instructions' => Craft::t('formie', 'Add, remove, or reorder option rows manually.'),
                'name' => 'options',
                'validation' => 'required|uniqueTableCellLabel|requiredTableCellLabel|requiredTableCellValue',
                'if' => 'displayType != "hidden" && optionsMode == "static"',
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
                        'label' => Craft::t('formie', 'Email'),
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
                'if' => 'displayType != "hidden"',
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

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;
        $id = $this->getHtmlId($form);
        $labelPosition = is_object($this->labelPosition) ? get_class($this->labelPosition) : (string)$this->labelPosition;
        $labelPosition = strtolower($labelPosition);
        $resolvedLabelPosition = str_contains($labelPosition, 'left') ? 'left' : (str_contains($labelPosition, 'right') ? 'right' : (str_contains($labelPosition, 'hidden') ? 'hidden' : 'above'));
        $isHiddenLabel = $context->get('labelPosition') instanceof HiddenPosition || $resolvedLabelPosition === 'hidden';

        if (in_array($this->displayType, ['checkboxes', 'radio'])) {
            if ($key === 'fieldLayout') {
                return SlotTag::make('fieldset')
                    ->core([
                        'data-formie-field-layout' => true,
                        'data-formie-recipients-field-layout' => true,
                        'data-formie-layout' => $this->layout ?? 'vertical',
                        'data-formie-label-position' => $resolvedLabelPosition,
                        'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
                    ])
                    ->theme([
                        'class' => [
                            'formie-field-layout',
                            'formie-recipients-field-layout',
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
                        'data-formie-recipients-field-label' => true,
                        'data-formie-label-position' => $resolvedLabelPosition,
                        'data-formie-sr-only' => $isHiddenLabel ? true : false,
                    ])
                    ->theme([
                        'class' => [
                            'formie-label',
                            'formie-field-label',
                            'formie-recipients-field-label',
                            $isHiddenLabel ? 'formie-sr-only' : false,
                        ],
                    ]);
            }
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        // CP field partials expect plain scalars/arrays: Craft's `select` and `text` macros cast `value` to string;
        // `checkboxGroup` expects an iterable list of selected option values (not a field value object).
        $templateValue = $value;
        if ($value instanceof RecipientsFieldValue) {
            $templateValue = match ($this->displayType) {
                'checkboxes' => $value->values(),
                'hidden' => is_array($value->rawValue())
                    ? Json::encode($value->rawValue())
                    : (string)($value->rawValue() ?? ''),
                default => $value->rawValue() ?? '',
            };
        }

        return Craft::$app->getView()->renderTemplate('formie/_formfields/recipients/input', [
            'name' => $this->handle,
            'value' => $templateValue,
            'field' => $this,
            'options' => $this->getResolvedOptions(),
        ]);
    }

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        if ($value instanceof RecipientsFieldValue) {
            return $value->toValueString();
        }

        if (is_array($value)) {
            return implode(', ', $value);
        }

        return (string)$value;
    }

    protected function defineValueForIntegration(mixed $value, IntegrationField $integrationField, IntegrationInterface $integration, ElementInterface $element = null, string $fieldKey = ''): mixed
    {
        if ($integrationField->getType() === IntegrationField::TYPE_ARRAY) {
            if ($value instanceof RecipientsFieldValue) {
                return $value->values();
            }

            // For hidden fields can have a plain array
            if (is_array($value)) {
                return $value;
            }

            if (is_string($value)) {
                return [$value];
            }

            return [(string)$value];
        }

        // Fetch the default handling
        return parent::defineValueForIntegration($value, $integrationField, $integration, $element);
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): string
    {
        if ($value instanceof RecipientsFieldValue) {
            return implode(', ', $value->labels());
        }

        // For hidden fields can have a plain array
        if (is_array($value)) {
            return implode(', ', $value);
        }

        return '';
    }

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        if ($this->displayType === 'checkboxes') {
            $values = $faker->randomElement($this->options)['value'] ?? '';
            
            return [$values];
        } else if ($this->displayType === 'dropdown' || $this->displayType === 'radio') {
            return $faker->randomElement($this->options)['value'] ?? '';
        } else if ($this->displayType === 'hidden') {
            return $faker->email;
        }
    }

    protected function defineValueForCondition(mixed $value, Submission $submission): mixed
    {
        // Recipients fields should use encoded values, because they can't be exposed in HTML source
        return $this->getValueAsString($this->getFakeValue($value), $submission);
    }

    protected function defineValueClass(): ?string
    {
        return RecipientsFieldValue::class;
    }

    protected function defineReferenceValues(): array
    {
        return [
            FieldReferenceValue::default([
                'variableTypes' => $this->displayType === 'checkboxes'
                    ? [Variables::TYPE_EMAIL, Variables::TYPE_CALCULATIONS, Variables::TYPE_BOOLEAN]
                    : [Variables::TYPE_EMAIL],
            ]),
        ];
    }

    protected function defineClientInput(): array
    {
        $clientInput = [
            'obfuscated' => true,
            'multiple' => $this->displayType === 'checkboxes',
        ];

        if ($this->displayType === 'dropdown') {
            $clientInput['options'] = $this->getFieldOptions();
        }

        if (in_array($this->displayType, ['checkboxes', 'radio'], true)) {
            $clientInput['options'] = $this->getFieldOptions();
            $clientInput['layout'] = $this->layout ?? 'vertical';
        }

        if ($this->displayType === 'checkboxes') {
            $clientInput['multiple'] = true;
        }

        if ($this->displayType !== 'hidden') {
            return array_merge(parent::defineClientInput(), $clientInput);
        }

        return array_merge(parent::defineClientInput(), $clientInput, [
            'privateValues' => true,
            'inputType' => 'hidden',
        ]);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = ['options', 'validateOptions'];

        return $rules;
    }

    public function validateOptions(): void
    {
        if ($this->getIsHidden() || $this->getOptionsMode() !== OptionsMode::STATIC) {
            return;
        }

        $labels = [];
        $hasDuplicateLabels = false;
        $emailValidator = new EmailValidator();

        foreach ($this->options as &$option) {
            $label = (string)($option['label'] ?? '');
            $value = (string)($option['value'] ?? '');

            if (isset($labels[$label])) {
                $option['label'] = [
                    'value' => $label,
                    'hasErrors' => true,
                ];

                $hasDuplicateLabels = true;
            }

            $labels[$label] = true;

            foreach ($this->_parseRecipientEmails($value) as $email) {
                if (!$emailValidator->validate($email)) {
                    $option['value'] = [
                        'value' => $value,
                        'hasErrors' => true,
                    ];
                    $this->addError('options', Craft::t('formie', '“{email}” is not a valid email address.', [
                        'email' => $email,
                    ]));

                    break;
                }
            }
        }
        unset($option);

        if ($hasDuplicateLabels) {
            $this->addError('options', Craft::t('app', 'All option labels must be unique.'));
        }
    }

    protected function setPrePopulatedValue(mixed $value): mixed
    {
        // Allow populating via label to keep things private
        if (is_string($value)) {
            foreach ($this->getResolvedOptions() as $key => $option) {
                if ((string)$option['label'] === (string)$value) {
                    $value = $option['value'];
                }
            }
        }

        return parent::setPrePopulatedValue($value);
    }

    private function _getResolvedRecipientOptionRows(): array
    {
        return RecipientOptionSelectionHelper::optionRows($this->getResolvedOptions());
    }

    private function _parseRecipientEmails(string $value): array
    {
        return array_values(array_filter(
            array_map('trim', explode(',', $value)),
            static fn(string $email): bool => $email !== '',
        ));
    }
}
