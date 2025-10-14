<?php
namespace verbb\formie\fields;

use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\data\MultiOptionsFieldData;
use verbb\formie\fields\data\OptionData;
use verbb\formie\fields\data\SingleOptionFieldData;
use verbb\formie\fields\Hidden as HiddenField;
use verbb\formie\gql\types\generators\FieldOptionGenerator;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\Notification;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\helpers\Component;
use craft\helpers\Json;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\Type;

use ReflectionClass;
use ReflectionProperty;

class Recipients extends Field implements PreviewableFieldInterface
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


    // Properties
    // =========================================================================

    public string $displayType = 'hidden';
    public ?string $emailValue = 'label';
    public array $options = [];
    public bool $multi = false;


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // Normalize the options
        if (array_key_exists('multiple', $config)) {
            $config['multi'] = ArrayHelper::remove($config, 'multiple');
        }

        parent::__construct($config);
    }

    public function getFieldTypeDefaults(): array
    {
        // Setup defaults for some values which can't be set in the property definition
        $settings = parent::getFieldTypeDefaults();
        $settings['labelPosition'] = HiddenPosition::class;

        return $settings;
    }

    public function getIsHidden(): bool
    {
        return $this->displayType === 'hidden';
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        $value = parent::normalizeValue($value, $element);

        if ($value instanceof MultiOptionsFieldData || $value instanceof SingleOptionFieldData) {
            return $value;
        }

        // For fields that store their content as JSON for arrays (checkboxes), convert it
        if (is_string($value) && ($value === '' || Json::isJsonObject($value))) {
            $value = Json::decodeIfJson($value);
        }

        // Ensure we're always dealing with real values. Fake values are used on front-end render.
        // Fake values will exists here if validation for the element fails.
        $value = $this->getRealValue($value);

        // For non-hidden fields, ensure we cast to option field data
        if ($this->displayType !== 'hidden') {
            // Normalize to an array of strings
            $selectedValues = [];

            foreach ((array)$value as $val) {
                if (is_array($val) && isset($val['value'])) {
                    $selectedValues[] = $val['value'];
                } else {
                    $selectedValues[] = (string)$val;
                }
            }

            $options = [];
            $optionValues = [];
            $optionLabels = [];

            foreach ($this->options() as $option) {
                $selected = in_array($option['value'], $selectedValues, true);
                $options[] = new OptionData($option['label'], $option['value'], $selected, true);
                $optionValues[] = (string)$option['value'];
                $optionLabels[] = (string)$option['label'];
            }

            if (in_array($this->displayType, ['dropdown', 'radio'])) {
                // Convert the value to a SingleOptionFieldData object
                $selectedValue = reset($selectedValues);
                $index = array_search($selectedValue, $optionValues, true);
                $valid = $index !== false;
                $label = $valid ? $optionLabels[$index] : null;
                $value = new SingleOptionFieldData($label, $selectedValue, true, $valid);
            } else if ($this->displayType === 'checkboxes') {
                // Convert the value to a MultiOptionsFieldData object
                $selectedOptions = [];

                foreach ($selectedValues as $selectedValue) {
                    $index = array_search($selectedValue, $optionValues, true);
                    $valid = $index !== false;
                    $label = $valid ? $optionLabels[$index] : null;
                    $selectedOptions[] = new OptionData($label, $selectedValue, true, $valid);
                }

                $value = new MultiOptionsFieldData($selectedOptions);
            }

            $value->setOptions($options);
        }

        return $value;
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        // If the values are being saved as option field data, save them instead as "plain" values.
        // These will also be normalised already, so dealing with real values.
        if ($value instanceof SingleOptionFieldData) {
            $value = (string)$value;
        }

        if ($value instanceof MultiOptionsFieldData) {
            $value = array_map(function($item) {
                return (string)$item;
            }, (array)$value);
        }


        return parent::serializeValue($value, $element);
    }

    public function getValueForCondition(mixed $value, Submission $submission): mixed
    {
        // Recipients fields should use encoded values, because they can't be exposed in HTML source
        return $this->getValueAsString($this->getFakeValue($value), $submission);
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/recipients/preview', [
            'field' => $this,
        ]);
    }

    public function options(): array
    {
        return $this->options;
    }

    public function getFieldOptions(): array
    {
        // Don't expose the value (email address) in the front end to prevent scraping
        $options = [];

        foreach ($this->options() as $key => $value) {
            $options[$key] = $value;

            // Swap the value with the index - if there is a value, otherwise leave blank
            if ($options[$key]['value']) {
                $options[$key]['value'] = 'id:' . $key;
            }
        }

        return $options;
    }

    public function getFrontEndInputOptions(Form $form, mixed $value, array $renderOptions = []): array
    {
        $inputOptions = parent::getFrontEndInputOptions($form, $value, $renderOptions);

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

        // Set the parent field and namespace, but in a specific way due to nested field handling.
        if ($this->getParentField()) {
            // Note the order here is important, due to Repeaters (and other nested fields)
            // can set the namespace with `setParentField()`, but we want to specifically use the
            // namespace value we already have, which has already neen set anyway.
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

            foreach ($this->options() as $option) {
                if (!empty($option['isDefault'])) {
                    $value[] = $option['value'];
                }
            }

            if ($this->displayType !== 'checkboxes') {
                $value = $value[0] ?? '';
            }
        }

        return $value;
    }

    public function getRealValue($value)
    {
        // This will convert fake values (`id:1`, `['id:2', 'id:3']`) into their real values (`email@`, `[`email@`, `email@`]`)
        // But will also just return the real value if it's already provided in that format.

        // For any array-compatible field types (and data), recursively iterate each item
        if (is_array($value)) {
            return array_map(function($item) {
                return $this->getRealValue($item);
            }, $value);
        }

        // Check if we need to replace the value - for fields that define options in CP
        if (str_contains($value, 'id:')) {
            // Replace each occurance of the `id:X` placeholder value with their real value
            $value = preg_replace_callback('/id:(\d+)/m', function(array $match) use ($value): string {
                $index = $match[1] ?? 0;

                return $this->options()[$index]['value'] ?? $value;
            }, $value);
        }

        // For hidden fields, there's no CP defined options, so decode its encoded value
        if (str_contains($value, 'base64:')) {
            $value = StringHelper::decdec($value);

            // Check if this was an array of data
            if (is_string($value) && Json::isJsonObject($value)) {
                $value = implode(',', array_filter(Json::decode($value)));
            }
        }

        return $value;
    }

    public function getFakeValue($value)
    {
        if (in_array($this->displayType, ['dropdown', 'radio'])) {
            foreach ($this->options() as $key => $option) {
                $id = 'id:' . $key;

                if ((string)$option['value'] === (string)$value) {
                    $value = new SingleOptionFieldData($option['label'], $id, true);

                    break;
                }
            }
        } else if ($this->displayType === 'checkboxes') {
            // Swap out the values with fake values
            $selectedValues = [];

            foreach ((array)$value as $val) {
                $selectedValues[] = (string)$val;
            }

            $options = [];

            foreach ($this->options() as $key => $option) {
                $id = 'id:' . $key;

                if (in_array((string)$option['value'], $selectedValues, true)) {
                    $options[] = new OptionData($option['label'], $id, true);
                }

            }
            $value = new MultiOptionsFieldData($options);
        } else if ($this->displayType === 'hidden') {
            // For a hidden field, there's no CP defined options, so encode the provided value
            // Also - support arrays of recipients in a hidden field
            if (is_array($value)) {
                $value = Json::encode($value);
            }

            $value = StringHelper::encenc((string)$value);
        }

        return $value;
    }

    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if (in_array($this->displayType, ['dropdown', 'radio'])) {
            return $value->value ? Craft::t('site', (string)$value->label) : '';
        } else if ($this->displayType === 'checkboxes') {
            $labels = [];

            foreach ($value as $option) {
                if ($option->value) {
                    $labels[] = Craft::t('site', $option->label);
                }
            }

            return implode(', ', $labels);
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
                'resolve' => function($field) {
                    return $field->multi;
                },
            ],
            'multi' => [
                'name' => 'multi',
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

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Display Type'),
                'help' => Craft::t('formie', 'Set different display layouts for this field.'),
                'name' => 'displayType',
                'options' => [
                    ['label' => Craft::t('formie', 'Hidden'), 'value' => 'hidden'],
                    ['label' => Craft::t('formie', 'Dropdown'), 'value' => 'dropdown'],
                    ['label' => Craft::t('formie', 'Checkboxes'), 'value' => 'checkboxes'],
                    ['label' => Craft::t('formie', 'Radio Buttons'), 'value' => 'radio'],
                ],
            ]),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Options'),
                'help' => Craft::t('formie', 'Define the available options for users to select from.'),
                'name' => 'options',
                'validation' => 'required|uniqueTableCellLabel|requiredTableCellLabel',
                'if' => '$get(displayType).value != hidden',
                'newRowDefaults' => [
                    'label' => '',
                    'value' => '',
                    'isDefault' => false,
                ],
                'columns' => [
                    [
                        'type' => 'label',
                        'label' => Craft::t('formie', 'Option Label'),
                        'class' => 'singleline-cell textual',
                    ],
                    [
                        'type' => 'value',
                        'label' => Craft::t('formie', 'Email'),
                        'class' => 'singleline-cell textual',
                    ],
                    [
                        'type' => 'default',
                        'label' => Craft::t('formie', 'Default'),
                        'class' => 'thin checkbox-cell',
                    ],
                ],
            ]),
        ];
    }

    public function defineSettingsSchema(): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Required Field'),
                'help' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
                'name' => 'required',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Error Message'),
                'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                'name' => 'errorMessage',
                'if' => '$get(required).value',
            ]),
            SchemaHelper::prePopulate(),
            SchemaHelper::includeInEmailField(),
            SchemaHelper::emailNotificationValue([
                'if' => '$get(displayType).value != hidden',
                'options' => [
                    ['label' => Craft::t('formie', 'Label'), 'value' => 'label'],
                    ['label' => Craft::t('formie', 'Value'), 'value' => 'value'],
                ],
            ]),
        ];
    }

    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
        ];
    }

    public function defineAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::inputAttributesField(),
            SchemaHelper::enableContentEncryptionField(),
        ];
    }

    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        $form = $context['form'] ?? null;

        $id = $this->getHtmlId($form);

        if (in_array($this->displayType, ['checkboxes', 'radio'])) {
            if ($key === 'fieldContainer') {
                return new HtmlTag('fieldset', [
                    'class' => 'fui-fieldset',
                    'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
                ]);
            }

            if ($key === 'fieldLabel') {
                $labelPosition = $context['labelPosition'] ?? null;

                return new HtmlTag('legend', [
                    'class' => [
                        'fui-legend',
                    ],
                    'data' => [
                        'field-label' => true,
                        'fui-sr-only' => $labelPosition instanceof HiddenPosition ? true : false,
                    ],
                ]);
            }
        }

        return parent::defineHtmlTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/recipients/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'options' => $this->options(),
        ]);
    }

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        if ($value instanceof MultiOptionsFieldData) {
            return implode(', ', array_map(function($item) {
                return $item->value;
            }, (array)$value));
        }

        // For hidden fields can have a plain array
        if (is_array($value)) {
            return implode(', ', $value);
        }

        if (is_string($value)) {
            return $value;
        }

        return $value->value ?? '';
    }

    protected function defineValueForIntegration(mixed $value, IntegrationField $integrationField, IntegrationInterface $integration, ElementInterface $element = null, string $fieldKey = ''): mixed
    {
        // If mapping to an array, extract just the values
        if ($integrationField->getType() === IntegrationField::TYPE_ARRAY) {
            if ($value instanceof MultiOptionsFieldData) {
                return array_map(function($item) {
                    return $item->value;
                }, (array)$value);
            }

            // For hidden fields can have a plain array
            if (is_array($value)) {
                return $value;
            }

            if (is_string($value)) {
                return [$value];
            }

            return [$value->value];
        }

        // Fetch the default handling
        return parent::defineValueForIntegration($value, $integrationField, $integration, $element);
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): string
    {
        if ($value instanceof MultiOptionsFieldData) {
            return implode(', ', array_map(function($item) {
                return $item->label;
            }, (array)$value));
        }

        // For hidden fields can have a plain array
        if (is_array($value)) {
            return implode(', ', $value);
        }

        return $value->label ?? '';
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

    protected function defineValueForVariable(mixed $value, Submission $submission, Notification $notification): mixed
    {
        // Respect the format picker for "Email Notification Value" 
        if ($value instanceof SingleOptionFieldData) {
            return $this->emailValue === 'label' ? $value->label : $value->value;
        }

        return parent::defineValueForVariable($value, $submission, $notification);
    }

    protected function defineValueForVariableRaw(mixed $value, Submission $submission, Notification $notification): mixed
    {
        // TODO: add handling to be able to determine if this is raw or formatted values
        return $this->getValueAsString($value, $submission, $notification);
    }

    protected function setPrePopulatedValue(mixed $value): mixed
    {
        // Allow populating via label to keep things private
        if (is_string($value)) {
            foreach ($this->options() as $key => $option) {
                if ((string)$option['label'] === (string)$value) {
                    $value = $option['value'];
                }
            }
        }

        return parent::setPrePopulatedValue($value);
    }
}
