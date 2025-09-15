<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\conditions\OptionsFieldConditionRule;
use verbb\formie\fields\data\MultiOptionsFieldData;
use verbb\formie\fields\data\OptionData;
use verbb\formie\fields\data\SingleOptionFieldData;
use verbb\formie\gql\arguments\OptionFieldArguments;
use verbb\formie\gql\resolvers\OptionFieldResolver;
use verbb\formie\gql\types\generators\FieldOptionGenerator;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;

use Craft;
use craft\base\InlineEditableFieldInterface;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\db\QueryParam;
use craft\helpers\Json;

use yii\db\Schema;

use GraphQL\Type\Definition\Type;

use Throwable;

abstract class OptionsField extends Field implements InlineEditableFieldInterface, OptionsFieldInterface, PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function dbType(): string
    {
        return Schema::TYPE_STRING;
    }

    public static function queryCondition(array $instances, mixed $value, array &$params): ?array
    {
        $field = $instances[0] ?? null;

        if ($field && $field->multi) {
            $param = QueryParam::parse($value);

            if (empty($param->values)) {
                return null;
            }

            if ($param->operator === QueryParam::NOT) {
                $param->operator = QueryParam::OR;
                $negate = true;
            } else {
                $negate = false;
            }

            $condition = [$param->operator];
            $qb = Craft::$app->getDb()->getQueryBuilder();
            $valueSql = static::valueSql($instances);

            foreach ($param->values as $value) {
                $condition[] = $qb->jsonContains($valueSql, $value);
            }

            return $negate ? ['not', $condition] : $condition;
        }

        return parent::queryCondition($instances, $value, $params);
    }


    // Properties
    // =========================================================================

    public bool $multi = false;
    public ?string $layout = null;
    public array $options = [];
    public bool $optgroups = false;
    public bool $hasMultiNamespace = false;
    public ?string $emailValue = 'label';


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // Setup default options
        if (!array_key_exists('options', $config) && empty($config['options'])) {
            $config['options'] = $this->getDefaultOptions();
        }

        // Normalize the options
        $options = [];

        if (isset($config['options']) && is_array($config['options'])) {
            foreach ($config['options'] as $key => $option) {
                // Old school?
                if (!is_array($option)) {
                    $options[] = [
                        'label' => $option,
                        'value' => $key,
                        'isDefault' => '',
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

    public function options(): array
    {
        return $this->options;
    }

    public function getDefaultOptions(): array
    {
        return [];
    }

    public function allowDuplicateLabels(): bool
    {
        return false;
    }

    public function allowDuplicateValues(): bool
    {
        return false;
    }

    public function validateOptions(): void
    {
        $labels = [];
        $values = [];
        $hasDuplicateLabels = false;
        $hasDuplicateValues = false;
        $optgroup = '__root__';

        foreach ($this->options() as &$option) {
            // Ignore optgroups
            if (array_key_exists('optgroup', $option)) {
                $optgroup = $option['optgroup'];
                continue;
            }

            $label = (string)$option['label'] ?? '';
            $value = (string)$option['value'] ?? '';

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

        if (!$this->allowDuplicateLabels() && $hasDuplicateLabels) {
            $this->addError('options', Craft::t('app', 'All option labels must be unique.'));
        }

        if (!$this->allowDuplicateValues() && $hasDuplicateValues) {
            $this->addError('options', Craft::t('app', 'All option values must be unique.'));
        }
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($value instanceof MultiOptionsFieldData || $value instanceof SingleOptionFieldData) {
            return $value;
        }

        // Ensure multi-option fields are normalized separately first
        if ($value === '' && $this->multi) {
            $value = [];
        }

        if (is_string($value) && Json::isJsonObject($value)) {
            $value = Json::decodeIfJson($value);
        } else if (is_string($value) && strtolower($value) === '__blank__') {
            $value = '';
        } else if (empty($value) && $this->isFresh($element)) {
            $value = $this->defaultValue();
        }

        // Normalize to an array of strings
        $selectedValues = [];

        foreach ((array)$value as $val) {
            $selectedValues[] = (string)$val;
        }

        $selectedBlankOption = false;
        $options = [];
        $optionValues = [];
        $optionLabels = [];

        foreach ($this->options() as $option) {
            if (!isset($option['optgroup'])) {
                $selected = $this->isOptionSelected($option, $value, $selectedValues, $selectedBlankOption);
                $options[] = new OptionData($option['label'], (string)$option['value'], $selected, true);
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
            $index = array_search($selectedValue, $optionValues, false);
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
        } else if ($value instanceof SingleOptionFieldData) {
            return $value->value;
        }

        return parent::serializeValue($value, $element);
    }

    public function getElementConditionRuleType(): array|string|null
    {
        return OptionsFieldConditionRule::class;
    }

    public function getElementValidationRules(): array
    {
        // Get all of the acceptable values
        $range = parent::getElementValidationRules();

        foreach ($this->options() as $option) {
            if (!isset($option['optgroup'])) {
                // Cast the option value to a string in case it is an integer
                $range[] = (string)$option['value'];
            }
        }

        $rules[] = ['in', 'range' => $range, 'allowArray' => $this->multi];

        return $rules;
    }

    public function isValueEmpty(mixed $value, ?ElementInterface $element): bool
    {
        /** @var MultiOptionsFieldData|SingleOptionFieldData $value */
        if ($value instanceof SingleOptionFieldData) {
            return $value->value === null || $value->value === '';
        }

        if ($value instanceof MultiOptionsFieldData) {
            return count($value) === 0;
        }

        return parent::isValueEmpty($value, $element);
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

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'multi' => [
                'name' => 'multi',
                'type' => Type::boolean(),
            ],
            'layout' => [
                'name' => 'layout',
                'type' => Type::string(),
            ],
            'options' => [
                'name' => 'options',
                'type' => Type::listOf(FieldOptionGenerator::generateType()),
                'resolve' => function($field) {
                    return $field->options();
                },
            ],
        ]);
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

        foreach ($this->options() as $option) {
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

    protected function defineValueForVariable(mixed $value, Submission $submission, Notification $notification): mixed
    {
        // Respect the format picker for "Email Notification Value" 
        if ($value instanceof SingleOptionFieldData) {
            return $this->emailValue === 'label' ? $value->label : $value->value;
        }

        return parent::defineValueForVariable($value, $submission, $notification);
    }

    protected function getPredefinedOptions(): array
    {
        return Formie::$plugin->getPredefinedOptions()->getPredefinedOptions();
    }

    protected function setPrePopulatedValue(mixed $value): mixed
    {
        if ($this->multi) {
            return explode(',', $value);
        }

        return parent::setPrePopulatedValue($value);
    }

    protected function isOptionSelected(array $option, mixed $value, array &$selectedValues, bool &$selectedBlankOption): bool
    {
        return in_array((string)$option['value'], $selectedValues, true);
    }

    protected function translatedOptions(): array
    {
        $translatedOptions = [];

        foreach ($this->options() as $option) {
            if (isset($option['optgroup'])) {
                $translatedOptions[] = [
                    'optgroup' => Craft::t('formie', $option['optgroup']),
                ];
            } else {
                $translatedOptions[] = [
                    'label' => Craft::t('formie', $option['label']),
                    'value' => (string)$option['value'],
                ];
            }
        }

        return $translatedOptions;
    }

    protected function defaultValue(): array|string|null
    {
        if ($this->multi) {
            $defaultValues = [];

            foreach ($this->options() as $option) {
                if (!empty($option['isDefault'])) {
                    $defaultValues[] = (string)$option['value'];
                }
            }

            return $defaultValues;
        }

        foreach ($this->options() as $option) {
            if (!empty($option['isDefault'])) {
                return (string)$option['value'];
            }
        }

        return null;
    }

    protected function getFieldInputOptionValue(array $context = [])
    {
        // Returns the string to represent the ID for a selected option for the `fieldInput` theme config property
        // A little more involved due to needing to append the index of the option as just using  `StringHelper::toKebabCase()`
        // will strip out special-characters (e.g. `Option+` is `option`)
        $options = $context['fieldOptions'] ?? [];
        $option = $context['option'] ?? null;

        // Find the index first
        $optionIndex = array_search($option, $options);

        // Append it to the value picked, and ensure it's cleaned up
        $optionValue = $context['option']['value'] ?? '';

        if ($optionValue && $optionIndex !== false) {
            $optionValue .= '-' . $optionIndex;
        }

        return StringHelper::toKebabCase($optionValue);
    }
}
