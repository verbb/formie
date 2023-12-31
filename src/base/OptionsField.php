<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\IntegrationField;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\fields\data\MultiOptionsFieldData;
use craft\fields\data\OptionData;
use craft\fields\data\SingleOptionFieldData;
use craft\gql\arguments\OptionField as OptionFieldArguments;
use craft\gql\resolvers\OptionField as OptionFieldResolver;
use craft\helpers\Json;

use yii\db\Schema;

use GraphQL\Type\Definition\Type;

use Throwable;

abstract class OptionsField extends FormField implements PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function dbType(): string
    {
        return Schema::TYPE_STRING;
    }


    // Properties
    // =========================================================================

    public bool $multi = false;
    public bool $optgroups = false;
    public ?string $layout = null;
    public bool $hasMultiNamespace = false;
    public array $options = [];


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // Normalize the options
        $options = [];

        if (isset($config['options']) && is_array($config['options'])) {
            foreach ($config['options'] as $key => $option) {
                // Old school?
                if (!is_array($option)) {
                    $options[] = [
                        'label' => $option,
                        'value' => $key,
                        'default' => '',
                    ];
                } elseif (!empty($option['isOptgroup'])) {
                    // isOptgroup will be set if this is a settings request
                    $options[] = [
                        'optgroup' => $option['label'],
                    ];
                } else {
                    unset($option['isOptgroup']);
                    $options[] = $option;
                }
            }
        }

        $config['options'] = $options;

        // remove unused settings
        unset($config['columnType'], $config['multiple']);

        parent::__construct($config);
    }

    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'options';
        $attributes[] = 'multi';
        $attributes[] = 'layout';

        return $attributes;
    }

    public function validateOptions(): void
    {
        $labels = [];
        $values = [];
        $hasDuplicateLabels = false;
        $hasDuplicateValues = false;
        $optgroup = '__root__';

        foreach ($this->options as &$option) {
            // Ignore optgroups
            if (array_key_exists('optgroup', $option)) {
                $optgroup = $option['optgroup'];
                continue;
            }

            $label = (string)$option['label'];
            $value = (string)$option['value'];

            if (isset($labels[$optgroup][$label])) {
                $option['label'] = [
                    'value' => $label,
                    'hasErrors' => true,
                ];

                $hasDuplicateLabels = true;
            }

            if (isset($values[$value])) {
                $option['value'] = [
                    'value' => $value,
                    'hasErrors' => true,
                ];

                $hasDuplicateValues = true;
            }

            $labels[$optgroup][$label] = $values[$value] = true;
        }

        if ($hasDuplicateLabels) {
            $this->addError('options', Craft::t('app', 'All option labels must be unique.'));
        }

        if ($hasDuplicateValues) {
            $this->addError('options', Craft::t('app', 'All option values must be unique.'));
        }
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($value instanceof MultiOptionsFieldData || $value instanceof SingleOptionFieldData) {
            return $value;
        }

        if (is_string($value) && Json::isJsonObject($value)) {
            $value = Json::decodeIfJson($value);
        } else if ($value === '' && $this->multi) {
            $value = [];
        } else if ($value === '__BLANK__') {
            $value = '';
        } else if ($value === null && $this->isFresh($element)) {
            $value = $this->defaultValue();
        }

        // Normalize to an array of strings
        $selectedValues = [];

        foreach ((array)$value as $val) {
            $val = (string)$val;

            if (str_starts_with($val, 'base64:')) {
                $val = base64_decode(StringHelper::removeLeft($val, 'base64:'));
            }

            $selectedValues[] = $val;
        }

        $selectedBlankOption = false;
        $options = [];
        $optionValues = [];
        $optionLabels = [];

        foreach ($this->options() as $option) {
            if (!isset($option['optgroup'])) {
                $selected = $this->isOptionSelected($option, $value, $selectedValues, $selectedBlankOption);
                $options[] = new OptionData($option['label'], $option['value'], $selected, true);
                $optionValues[] = (string)$option['value'];
                $optionLabels[] = (string)$option['label'];
            }
        }

        if ($this->multi) {
            // Convert the value to a MultiOptionsFieldData object
            $selectedOptions = [];

            foreach ($selectedValues as $selectedValue) {
                $index = array_search($selectedValue, $optionValues, true);
                $valid = $index !== false;
                $label = $valid ? $optionLabels[$index] : null;
                $selectedOptions[] = new OptionData($label, $selectedValue, true, $valid);
            }

            $value = new MultiOptionsFieldData($selectedOptions);
        } else if (!empty($selectedValues)) {
            // Convert the value to a SingleOptionFieldData object
            $selectedValue = reset($selectedValues);
            $index = array_search($selectedValue, $optionValues, true);
            $valid = $index !== false;
            $label = $valid ? $optionLabels[$index] : null;
            $value = new SingleOptionFieldData($label, $selectedValue, true, $valid);
        } else {
            $value = new SingleOptionFieldData(null, null, true, false);
        }

        $value->setOptions($options);

        return $value;
    }

    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($value instanceof MultiOptionsFieldData) {
            $serialized = [];

            foreach ($value as $selectedValue) {
                /** @var OptionData $selectedValue */
                $serialized[] = $selectedValue->value;
            }

            return $serialized;
        }

        return parent::serializeValue($value, $element);
    }

    public function getElementValidationRules(): array
    {
        // Get all of the acceptable values
        $range = [];

        foreach ($this->options() as $option) {
            if (!isset($option['optgroup'])) {
                // Cast the option value to a string in case it is an integer
                $range[] = (string)$option['value'];
            }
        }

        return [
            [
                'in',
                'range' => $range,
                'allowArray' => $this->multi,
                // Don't allow saving invalid blank values via Selectize
                'skipOnEmpty' => !($this instanceof Dropdown && Craft::$app->getRequest()->getIsCpRequest()),
            ],
        ];
    }

    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        /** @var MultiOptionsFieldData|SingleOptionFieldData $value */
        if ($value instanceof SingleOptionFieldData) {
            return $value->value === null || $value->value === '';
        }

        return count($value) === 0;
    }

    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if ($this->multi) {
            /** @var MultiOptionsFieldData $value */
            $labels = [];

            foreach ($value as $option) {
                /** @var OptionData $option */
                if ($option->value) {
                    $labels[] = Craft::t('site', $option->label);
                }
            }

            return implode(', ', $labels);
        }

        /** @var SingleOptionFieldData $value */
        return $value->value ? Craft::t('site', (string)$value->label) : '';
    }

    public function getIsMultiOptionsField(): bool
    {
        return $this->multi;
    }

    public function getContentGqlType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => $this->multi ? Type::listOf(Type::string()) : Type::string(),
            'args' => OptionFieldArguments::getArguments(),
            'resolve' => OptionFieldResolver::class . '::resolve',
        ];
    }

    public function getContentGqlMutationArgumentType(): Type|array
    {
        $values = [];

        foreach ($this->options as $option) {
            if (!isset($option['optgroup'])) {
                $values[] = '“' . $option['value'] . '”';
            }
        }

        return [
            'name' => $this->handle,
            'type' => $this->multi ? Type::listOf(Type::string()) : Type::string(),
            'description' => Craft::t('app', 'The allowed values are [{values}]', ['values' => implode(', ', $values)]),
        ];
    }


    // Protected Methods
    // =========================================================================

    abstract protected function optionsSettingLabel(): string;

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = ['options', 'validateOptions'];

        return $rules;
    }

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        if ($value instanceof MultiOptionsFieldData) {
            return implode(', ', array_map(function($item) {
                return $item->value;
            }, (array)$value));
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

        return $value->label ?? '';
    }

    protected function getPredefinedOptions(): array
    {
        return Formie::$plugin->getPredefinedOptions()->getPredefinedOptions();
    }

    protected function setPrePopulatedValue($value): mixed
    {
        if ($this->multi) {
            return explode(',', $value);
        }

        return parent::setPrePopulatedValue($value);
    }

    protected function options(): array
    {
        return $this->options ?? [];
    }

    protected function isOptionSelected(array $option, mixed $value, array &$selectedValues, bool &$selectedBlankOption): bool
    {
        return in_array($option['value'], $selectedValues, true);
    }

    protected function searchKeywords(mixed $value, ElementInterface $element): string
    {
        $keywords = [];

        if ($this->multi) {
            /** @var MultiOptionsFieldData|OptionData[] $value */
            foreach ($value as $option) {
                $keywords[] = $option->value;
                $keywords[] = $option->label;
            }
        } else {
            /** @var SingleOptionFieldData $value */
            if ($value->value !== null) {
                $keywords[] = $value->value;
                $keywords[] = $value->label;
            }
        }

        return implode(' ', $keywords);
    }

    protected function translatedOptions(bool $encode = false, mixed $value = null, ?ElementInterface $element = null): array
    {
        $options = $this->options();
        $translatedOptions = [];

        foreach ($options as $option) {
            if (isset($option['optgroup'])) {
                $translatedOptions[] = [
                    'optgroup' => Craft::t('site', $option['optgroup']),
                ];
            } else {
                $translatedOptions[] = [
                    'label' => Craft::t('site', $option['label']),
                    'value' => $encode ? $this->encodeValue($option['value']) : $option['value'],
                ];
            }
        }

        return $translatedOptions;
    }

    protected function encodeValue(OptionData|MultiOptionsFieldData|string|null $value): string|array|null
    {
        if ($value instanceof MultiOptionsFieldData) {
            /** @var OptionData[] $options */
            $options = (array)$value;
            return array_map(fn(OptionData $value) => $this->encodeValue($value), $options);
        }

        if ($value instanceof OptionData) {
            $value = $value->value;
        }

        if ($value === null || $value === '') {
            return $value;
        }

        return sprintf('base64:%s', base64_encode($value));
    }

    protected function defaultValue(): array|string|null
    {
        if ($this->multi) {
            $defaultValues = [];

            foreach ($this->options() as $option) {
                if (!empty($option['default'])) {
                    $defaultValues[] = $option['value'];
                }
            }

            return $defaultValues;
        }

        foreach ($this->options() as $option) {
            if (!empty($option['default'])) {
                return $option['value'];
            }
        }

        return null;
    }
}
