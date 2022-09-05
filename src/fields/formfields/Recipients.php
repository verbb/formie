<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\formfields\Hidden as HiddenField;
use verbb\formie\gql\types\generators\FieldOptionGenerator;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\Notification;
use verbb\formie\positions\Hidden;

use Craft;
use craft\base\ElementInterface;
use craft\fields\data\MultiOptionsFieldData;
use craft\fields\data\OptionData;
use craft\fields\data\SingleOptionFieldData;
use craft\helpers\Component;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use ReflectionClass;
use ReflectionProperty;

use GraphQL\Type\Definition\Type;

class Recipients extends FormField
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Recipients');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/recipients/icon.svg';
    }


    // Properties
    // =========================================================================

    public string $displayType = 'hidden';
    public array $options = [];
    public ?bool $multiple = null;


    // Public Methods
    // =========================================================================

    public function getIsHidden(): bool
    {
        return $this->displayType === 'hidden';
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        $value = parent::normalizeValue($value, $element);

        if ($value instanceof MultiOptionsFieldData || $value instanceof SingleOptionFieldData) {
            return $value;
        }

        // For fields that store their content as JSON for arrays (checkboxes), convert it
        if (is_string($value) && ($value === '' || str_starts_with($value, '[') || str_starts_with($value, '{'))) {
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

    /**
     * @inheritdoc
     */
    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
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

    /**
     * @inheritDoc
     */
    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/recipients/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'options' => $this->options(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/recipients/preview', [
            'field' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getEmailHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        return [
            'labelPosition' => Hidden::class,
        ];
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

    /**
     * @inheritDoc
     */
    public function getFrontEndInputOptions(Form $form, mixed $value, array $renderOptions = []): array
    {
        $inputOptions = parent::getFrontEndInputOptions($form, $value, $renderOptions);

        // When rendering the value **always** swap out the real values with obscured ones
        $inputOptions['value'] = $this->getFakeValue($value);

        return $inputOptions;
    }

    /**
     * @inheritDoc
     */
    public function getDisplayTypeField(): FormFieldInterface
    {
        // Use all the same settings from this field, but remove any invalid ones
        $class = new ReflectionClass($this);

        $config = [
            'options' => $this->getFieldOptions(),
        ];

        // Set the parent field and namespace, but in a specific way due to nested field handling.
        if ($this->getParentField()) {
            // Note the order here is important, due to Repeaters (and other nested fields)
            // can set the namespace with `setParentFIeld()`, but we want to specifically use the
            // namespace value we already have, which has already neen set anyway.
            $config['parentField'] = $this->getParentField();
            $config['namespace'] = $this->getNamespace();
        } else {
            $config['namespace'] = $this->getNamespace();
        }

        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic() && $property->getDeclaringClass()->isAbstract()) {
                $config[$property->getName()] = $this->{$property->getName()};
            }
        }

        if ($this->displayType === 'hidden') {
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
    }

    public function getDefaultValue($attributePrefix = '')
    {
        $value = parent::getDefaultValue($attributePrefix) ?? $this->defaultValue;

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

    /**
     * @inheritDoc
     */
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
        if (strpos($value, 'id:') !== false) {
            // Replace each occurance of the `id:X` placeholder value with their real value
            $value = preg_replace_callback('/id:(\d+)/m', function(array $match) use ($value): string {
                $index = $match[1] ?? 0;

                return $this->options()[$index]['value'] ?? $value;
            }, $value);
        }

        // For hidden fields, there's no CP defined options, so decode its encoded value
        if (strpos($value, 'base64:') !== false) {
            $value = StringHelper::decdec($value);

            // Check if this was an array of data
            if (is_string($value) && Json::isJsonObject($value)) {
                $value = implode(',', array_filter(Json::decode($value)));
            }
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
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
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
        ];
    }

    /**
     * @inheritDoc
     */
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

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'options' => [
                'name' => 'options',
                'type' => Type::listOf(FieldOptionGenerator::generateType()),
            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getContentGqlMutationArgumentType(): array|Type
    {
        if ($this->displayType === 'checkboxes') {
            return Type::listOf(Type::string());
        }

        return Type::string();
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
                return new HtmlTag('legend', [
                    'class' => 'fui-legend',
                ]);
            }
        }

        return parent::defineHtmlTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    protected function defineValueAsString($value, ElementInterface $element = null): string
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

    protected function defineValueForIntegration($value, $integrationField, $integration, ElementInterface $element = null, $fieldKey = ''): mixed
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

    protected function defineValueForSummary($value, ElementInterface $element = null): string
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

    protected function options(): array
    {
        return $this->options ?? [];
    }
}
