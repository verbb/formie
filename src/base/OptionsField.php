<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\helpers\FieldOptionHelper;
use verbb\formie\helpers\OptionsMode;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\PreviewableFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\conditions\OptionsFieldConditionRule;
use verbb\formie\fields\definitions\FieldReferenceValue;
use verbb\formie\fields\definitions\FieldValueClass;
use verbb\formie\fields\values\MultiOptionFieldValue;
use verbb\formie\fields\values\OptionValue;
use verbb\formie\fields\values\SingleOptionFieldValue;
use verbb\formie\gql\arguments\OptionFieldArguments;
use verbb\formie\gql\resolvers\OptionFieldResolver;
use verbb\formie\gql\types\generators\FieldOptionGenerator;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Variables as FormieVariables;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;
use verbb\formie\models\OptionSource;
use verbb\formie\options\OptionSourceConfigHelper;
use verbb\formie\options\OptionSourceContext;
use verbb\formie\options\OptionSourceFieldInterface;
use verbb\formie\options\OptionSourceProviderHelper;
use verbb\formie\options\OptionSourceValidationMode;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Json;

use yii\db\ExpressionInterface;
use yii\db\Schema;

use GraphQL\Type\Definition\Type;

use Throwable;

abstract class OptionsField extends Field implements OptionsFieldInterface, OptionSourceFieldInterface, PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function dbType(): string
    {
        return Schema::TYPE_STRING;
    }

    public static function supportsGqlConfigProvider(): bool
    {
        return true;
    }

    public static function translatableProperties(): array
    {
        $properties = parent::translatableProperties();
        $properties[] = 'options';

        return $properties;
    }

    public static function queryCondition(array $instances, mixed $value, array &$params): array|string|ExpressionInterface|false|null
    {
        $firstInstance = $instances[0] ?? null;

        if (!$firstInstance instanceof self) {
            return parent::queryCondition($instances, $value, $params);
        }

        if ($firstInstance->multi) {
            $condition = self::_buildMultiOptionQueryCondition($instances, $value);

            if ($condition !== null) {
                return $condition;
            }

            return parent::queryCondition($instances, $value, $params);
        }

        $resolvedValue = self::_resolveSingleOptionCriteriaValue($instances, $value);

        if ($resolvedValue === false) {
            return false;
        }

        return parent::queryCondition($instances, $resolvedValue, $params);
    }

    public static function gqlContentTypeFromConfig(array $config): Type|array
    {
        $settings = self::_getOptionsFieldConfigSettings($config);

        return [
            'name' => $config['handle'] ?? '',
            'type' => !empty($settings['multi']) ? Type::listOf(Type::string()) : Type::string(),
            'args' => OptionFieldArguments::getArguments(),
            'resolve' => OptionFieldResolver::class . '::resolve',
        ];
    }

    public static function gqlContentMutationArgumentTypeFromConfig(array $config): Type|array
    {
        $settings = self::_getOptionsFieldConfigSettings($config);

        return [
            'name' => $config['handle'] ?? '',
            'type' => !empty($settings['multi']) ? Type::listOf(Type::string()) : Type::string(),
            'description' => Craft::t('app', 'The allowed values are [{values}]', [
                'values' => implode(', ', self::_getOptionConfigLabels($settings)),
            ]),
        ];
    }

    private static function _resolveSingleOptionCriteriaValue(array $instances, mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if (array_key_exists('value', $value)) {
            $resolvedValue = $value['value'];

            if (array_key_exists('caseInsensitive', $value)) {
                return [
                    'value' => $resolvedValue,
                    'caseInsensitive' => (bool)$value['caseInsensitive'],
                ];
            }

            return $resolvedValue;
        }

        if (!array_key_exists('label', $value)) {
            return $value;
        }

        $labels = array_values(array_filter(array_map(static fn($label) => (string)$label, (array)$value['label']), static fn(string $label) => $label !== ''));
        $caseInsensitive = (bool)($value['caseInsensitive'] ?? false);

        if (!$labels) {
            return false;
        }

        $matchedValues = self::_resolveOptionValuesByLabels($instances, $labels, $caseInsensitive);

        if (!$matchedValues) {
            return false;
        }

        $resolvedValue = count($matchedValues) === 1 ? $matchedValues[0] : $matchedValues;

        if ($caseInsensitive) {
            return [
                'value' => $resolvedValue,
                'caseInsensitive' => true,
            ];
        }

        return $resolvedValue;
    }

    private static function _resolveOptionValuesByLabels(array $instances, array $labels, bool $caseInsensitive): array
    {
        $labelsLookup = [];

        foreach ($labels as $label) {
            $labelsLookup[$caseInsensitive ? StringHelper::toLowerCase($label) : $label] = true;
        }

        $matchedValues = [];

        foreach ($instances as $instance) {
            if (!$instance instanceof self) {
                continue;
            }

            foreach ($instance->getResolvedOptions() as $option) {
                if (isset($option['optgroup'])) {
                    continue;
                }

                $optionLabel = (string)($option['label'] ?? '');
                $lookupLabel = $caseInsensitive ? StringHelper::toLowerCase($optionLabel) : $optionLabel;

                if (!isset($labelsLookup[$lookupLabel])) {
                    continue;
                }

                $matchedValues[] = (string)($option['value'] ?? '');
            }
        }

        return array_values(array_unique($matchedValues));
    }

    private static function _getOptionsFieldConfigSettings(array $config): array
    {
        $settings = Json::decodeIfJson($config['settings'] ?? null);

        return is_array($settings) ? $settings : [];
    }

    private static function _getOptionConfigLabels(array $settings): array
    {
        $values = [];
        $options = $settings['options'] ?? [];

        if (OptionsMode::normalize($settings['optionsMode'] ?? null) === OptionsMode::DYNAMIC && Formie::$plugin) {
            try {
                $options = (new static($settings))->getResolvedOptions();
            } catch (Throwable) {
                $options = $settings['options'] ?? [];
            }
        }

        foreach ($options as $option) {
            if (!is_array($option) || isset($option['optgroup'])) {
                continue;
            }

            $values[] = '“' . ($option['value'] ?? '') . '”';
        }

        return $values;
    }

    private static function _buildMultiOptionQueryCondition(array $instances, mixed $value): array|string|ExpressionInterface|false|null
    {
        $valueSql = self::valueSql($instances);

        if ($valueSql === null) {
            return false;
        }

        $resolvedValues = self::_resolveMultiOptionCriteriaValues($instances, $value);

        if ($resolvedValues === false) {
            return false;
        }

        if ($resolvedValues === null) {
            return null;
        }

        if (!$resolvedValues) {
            return false;
        }

        $qb = Craft::$app->getDb()->getQueryBuilder();

        if (count($resolvedValues) === 1) {
            return $qb->jsonContains($valueSql, reset($resolvedValues));
        }

        // Multi-value criteria means "submission includes all of these options".
        return [
            'and',
            ...array_map(static fn(string $resolvedValue) => $qb->jsonContains($valueSql, $resolvedValue), $resolvedValues),
        ];
    }

    private static function _resolveMultiOptionCriteriaValues(array $instances, mixed $value): array|false|null
    {
        if (!is_array($value)) {
            if (is_object($value) || is_resource($value) || $value === null) {
                return false;
            }

            $scalar = (string)$value;

            if ($scalar === '') {
                return false;
            }

            return [(string)$scalar];
        }

        if (array_key_exists('value', $value)) {
            $values = array_values(array_filter(array_map(static fn($item) => (string)$item, (array)$value['value']), static fn(string $item) => $item !== ''));

            return $values ?: false;
        }

        if (array_key_exists('label', $value)) {
            $labels = array_values(array_filter(array_map(static fn($label) => (string)$label, (array)$value['label']), static fn(string $label) => $label !== ''));
            $caseInsensitive = (bool)($value['caseInsensitive'] ?? false);

            if (!$labels) {
                return false;
            }

            $resolvedValues = self::_resolveOptionValuesByLabels($instances, $labels, $caseInsensitive);

            return $resolvedValues ?: false;
        }

        return null;
    }


    // Properties
    // =========================================================================

    public bool $multi = false;
    public ?string $layout = null;
    public array $options = [];
    public string $optionsMode = OptionsMode::STATIC;
    public ?array $optionSource = null;
    public bool $optgroups = false;
    public ?string $emailFieldSummaryValue = 'label';
    public bool $hasMultiNamespace = false;


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
                        'default' => '',
                    ];
                } elseif (!empty($option['optgroup'])) {
                    // optgroup will be set if this is a settings request
                    $options[] = [
                        'optgroup' => $option['label'] ?? $option['optgroup'],
                    ];
                } else {
                    unset($option['optgroup']);
                    $options[] = $option;
                }
            }
        }

        $config['options'] = FieldOptionHelper::normalizeOptionRows($options);

        $config['optionsMode'] = OptionsMode::normalize($config['optionsMode'] ?? null);
        $config['optionSource'] = OptionSourceConfigHelper::normalizeOptionSource(
            $config['optionSource'] ?? null,
            $config['optionsMode'],
            OptionSourceConfigHelper::allowedTypesForFieldClass(static::class),
        );

        if ($config['optionsMode'] === OptionsMode::DYNAMIC && $config['optionSource'] === null) {
            $config['optionsMode'] = OptionsMode::STATIC;
        }

        // remove unused settings
        unset($config['columnType'], $config['multiple']);

        parent::__construct($config);
    }

    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'options';
        $attributes[] = 'optionsMode';
        $attributes[] = 'optionSource';
        $attributes[] = 'multi';
        $attributes[] = 'layout';

        return $attributes;
    }

    public function options(): array
    {
        return $this->options;
    }

    public function getOptionsMode(): string
    {
        return OptionsMode::normalize($this->optionsMode);
    }

    public function getOptionSource(): ?OptionSource
    {
        return OptionSource::fromConfig($this->optionSource);
    }

    public function usesStrictOptionValidation(): bool
    {
        if ($this->getOptionsMode() !== OptionsMode::DYNAMIC || !Formie::$plugin) {
            return OptionsMode::usesStrictInValidation($this->getOptionsMode());
        }

        return Formie::$plugin->getOptionSources()->getValidationMode($this) === OptionSourceValidationMode::STRICT;
    }

    public function getValidationOptionValues(): array
    {
        $values = [];
        $options = $this->getResolvedOptions();

        if ($this->getOptionsMode() === OptionsMode::DYNAMIC && Formie::$plugin) {
            $options = Formie::$plugin->getOptionSources()->resolveRows($this, new OptionSourceContext(
                scope: OptionSourceContext::SCOPE_VALIDATE,
            ));
        }

        foreach ($options as $option) {
            if (!isset($option['optgroup'])) {
                $values[] = (string)$option['value'];
            }
        }

        return $values;
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

    public function shouldPersistOptionLabels(): bool
    {
        return $this->getOptionsMode() !== OptionsMode::STATIC;
    }

    public static function normalizeSnapshotFieldSettings(array $settings): array
    {
        if (OptionsMode::normalize($settings['optionsMode'] ?? null) === OptionsMode::STATIC) {
            return $settings;
        }

        unset($settings['options']);

        return $settings;
    }

    /**
     * Resolve an option row’s front-end availability.
     *
     * Legacy `disabled: true` rows meant “hide from form” (#824) and map to `hidden`.
     */
    public static function resolveOptionAvailability(array $option): ?string
    {
        $availability = $option['availability'] ?? null;

        if ($availability === 'hidden' || $availability === 'disabled') {
            return $availability;
        }

        if (!empty($option['disabled'])) {
            return 'hidden';
        }

        return null;
    }

    public static function isOptionHidden(array $option): bool
    {
        return self::resolveOptionAvailability($option) === 'hidden';
    }

    public static function isOptionFrontEndDisabled(array $option): bool
    {
        return self::resolveOptionAvailability($option) === 'disabled';
    }

    /**
     * Options exposed to the front-end form (hidden rows excluded).
     *
     * Hidden rows stay in field settings so existing submission values and labels still resolve.
     */
    public function getFieldOptions(): array
    {
        $options = [];

        foreach ($this->getResolvedOptions() as $option) {
            if (isset($option['optgroup'])) {
                $options[] = $option;
                continue;
            }

            if (self::isOptionHidden($option)) {
                continue;
            }

            $options[] = array_merge($option, [
                'disabled' => self::isOptionFrontEndDisabled($option),
            ]);
        }

        return $options;
    }

    public function getFormBuilderSettings(): array
    {
        $settings = parent::getFormBuilderSettings();

        if ($this->getOptionsMode() !== OptionsMode::STATIC) {
            // Builder preview controls need resolved rows, but dynamic fields should
            // keep saved settings driven by `optionSource` rather than `options`.
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
        if ($this->getOptionsMode() !== OptionsMode::STATIC) {
            return;
        }

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

            $label = (string)($option['label'] ?? '');
            $value = (string)($option['value'] ?? '');

            // Ignore incomplete placeholder rows from the form builder.
            if ($label === '' && $value === '') {
                continue;
            }

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
        if ($value instanceof MultiOptionFieldValue || $value instanceof SingleOptionFieldValue) {
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
        } elseif ($value === null && $this->isFresh($element)) {
            $value = $this->defaultValue();
        }

        // Dynamic providers submit labels alongside values for durable display;
        // validation still treats resolved server-side values as authoritative.
        $submittedLabelsByValue = [];

        if (is_array($value) && array_key_exists('value', $value) && !array_is_list($value)) {
            $submittedLabelsByValue[(string)$value['value']] = isset($value['label']) ? (string)$value['label'] : null;
            $value = $value['value'];
        }

        // Normalize incoming value(s) to a list of selected scalar values.
        $selectedValues = [];

        if ($value instanceof MultiOptionFieldValue) {
            $selectedValues = $value->values();
        } else if ($value instanceof SingleOptionFieldValue) {
            $selectedValues = [$value->value ?? ''];
        } else if ($value instanceof OptionValue) {
            $selectedValues = [$value->value ?? ''];
        } else if (is_array($value)) {
            foreach ($value as $val) {
                if ($val instanceof OptionValue) {
                    $selectedValues[] = (string)($val->value ?? '');
                    continue;
                }

                if (is_array($val) && array_key_exists('value', $val)) {
                    $selectedValues[] = (string)$val['value'];
                    if (array_key_exists('label', $val)) {
                        $submittedLabelsByValue[(string)$val['value']] = (string)$val['label'];
                    }
                    continue;
                }

                if (is_scalar($val) || $val === null) {
                    $selectedValues[] = (string)$val;
                }
            }
        } else if (is_scalar($value) || $value === null) {
            $selectedValues[] = (string)$value;
        }

        // Treat blank selections as no selection for option fields.
        $selectedValues = array_values(array_filter(
            array_map(static fn($item) => (string)$item, $selectedValues),
            static fn(string $item) => $item !== ''
        ));

        $options = [];
        $optionLabelsByValue = [];

        foreach ($this->getResolvedOptions() as $option) {
            if (isset($option['optgroup'])) {
                continue;
            }

            $optionValue = (string)($option['value'] ?? '');
            $optionLabel = (string)($option['label'] ?? '');
            $selected = in_array($optionValue, $selectedValues, true);

            $options[] = new OptionValue($optionLabel, $optionValue, $selected, true);

            if (!array_key_exists($optionValue, $optionLabelsByValue)) {
                $optionLabelsByValue[$optionValue] = $optionLabel;
            }
        }

        if ($this->multi && !empty($selectedValues)) {
            $selectedOptions = [];

            foreach ($selectedValues as $selectedValue) {
                $selectedValue = (string)$selectedValue;
                $valid = array_key_exists($selectedValue, $optionLabelsByValue);
                $label = $submittedLabelsByValue[$selectedValue]
                    ?? ($valid ? $optionLabelsByValue[$selectedValue] : null);

                if (!$this->usesStrictOptionValidation()) {
                    if ($label === null) {
                        $label = $selectedValue;
                    }
                    $valid = true;
                }

                $selectedOptions[] = new OptionValue($label, $selectedValue, true, $valid);
            }

            $normalizedValue = new MultiOptionFieldValue($selectedOptions);
        } else if (!empty($selectedValues)) {
            $selectedValue = (string)reset($selectedValues);
            $valid = array_key_exists($selectedValue, $optionLabelsByValue);
            $label = $submittedLabelsByValue[$selectedValue]
                ?? ($valid ? $optionLabelsByValue[$selectedValue] : null);

            if (!$this->usesStrictOptionValidation()) {
                if ($label === null) {
                    $label = $selectedValue;
                }
                $valid = true;
            }

            $normalizedValue = new SingleOptionFieldValue($label, $selectedValue, true, $valid);
        } else {
            $normalizedValue = null;
        }

        if ($normalizedValue) {
            $normalizedValue->setOptions($options);
        }

        return $normalizedValue;
    }

    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($value instanceof MultiOptionFieldValue) {
            if (!$this->shouldPersistOptionLabels()) {
                return $value->values();
            }

            return array_map(static fn(OptionValue $option) => [
                'value' => $option->value,
                'label' => $option->getDisplayLabel(),
            ], $value->all());
        }

        if ($value instanceof SingleOptionFieldValue) {
            if (!$this->shouldPersistOptionLabels()) {
                return $value->value;
            }

            return [
                'value' => $value->value,
                'label' => $value->getDisplayLabel(),
            ];
        }

        return parent::serializeValue($value, $element);
    }

    public function getElementConditionRuleType(): array|string|null
    {
        return OptionsFieldConditionRule::class;
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        if (!$this->usesStrictOptionValidation()) {
            return $rules;
        }

        $rules[] = [$this->handle, 'in', 'range' => $this->getValidationOptionValues(), 'allowArray' => $this->multi];

        return $rules;
    }

    public function isValueEmpty(mixed $value, ?ElementInterface $element): bool
    {
        if ($value === null) {
            return true;
        }

        if ($value instanceof MultiOptionFieldValue || $value instanceof SingleOptionFieldValue) {
            return $value->isEmpty();
        }

        return parent::isValueEmpty($value, $element);
    }

    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if ($this->multi) {
            if (!($value instanceof MultiOptionFieldValue)) {
                return '';
            }

            /** @var MultiOptionFieldValue $value */
            $labels = [];

            foreach ($value as $option) {
                /** @var OptionValue $option */
                if ($option->value) {
                    $labels[] = $option->getDisplayLabel();
                }
            }

            return $this->renderPreviewText(implode(', ', $labels));
        }

        if (!($value instanceof SingleOptionFieldValue)) {
            return '';
        }

        return $value->value ? $this->renderPreviewText($value->getDisplayLabel()) : '';
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
                    return $field->getResolvedOptions();
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

        foreach ($this->getResolvedOptions() as $option) {
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
        if ($value instanceof MultiOptionFieldValue) {
            return implode(', ', $value->values());
        }

        if ($value instanceof SingleOptionFieldValue) {
            return $value->value ?? '';
        }

        return '';
    }

    protected function defineValueAsArray(mixed $value, ElementInterface $element = null): mixed
    {
        if ($value instanceof MultiOptionFieldValue) {
            return $value->values();
        }

        if ($value instanceof SingleOptionFieldValue) {
            return ($value->value !== null && $value->value !== '') ? [$value->value] : [];
        }

        return [];
    }

    protected function defineValueForIntegration(mixed $value, IntegrationField $integrationField, IntegrationInterface $integration, ElementInterface $element = null, string $fieldKey = ''): mixed
    {
        // If mapping to an array, extract just the values
        if ($integrationField->getType() === IntegrationField::TYPE_ARRAY) {
            if ($value instanceof MultiOptionFieldValue) {
                return $value->values();
            }

            if ($value instanceof SingleOptionFieldValue) {
                return [$value->value];
            }

            return [];
        }

        // Fetch the default handling
        return parent::defineValueForIntegration($value, $integrationField, $integration, $element);
    }

    protected function defineReferenceValues(): array
    {
        $primaryTypes = $this->definePrimaryOptionVariableSourceTypes();

        return [
            FieldReferenceValue::default([
                'variableTypes' => $primaryTypes,
            ]),
            FieldReferenceValue::property([
                'handle' => 'label',
                'label' => Craft::t('formie', 'Label'),
                'supportsClient' => false,
                'variableTypes' => [FormieVariables::TYPE_TEXT],
            ]),
            FieldReferenceValue::property([
                'handle' => 'value',
                'label' => Craft::t('formie', 'Value'),
                'supportsClient' => false,
                'variableTypes' => $primaryTypes,
            ]),
        ];
    }

    protected function definePrimaryOptionVariableSourceTypes(): array
    {
        return [FormieVariables::TYPE_TEXT];
    }

    protected function defineValueClass(): ?string
    {
        return $this->multi ? MultiOptionFieldValue::class : SingleOptionFieldValue::class;
    }

    public function fieldKind(): string
    {
        $displayType = (string)($this->displayType ?? 'dropdown');

        return match ($displayType) {
            'radio' => self::KIND_RADIO_GROUP,
            'checkboxes' => self::KIND_CHECKBOX_GROUP,
            default => self::KIND_SELECT,
        };
    }

    protected function defineClientInput(): array
    {
        $displayType = (string)($this->displayType ?? 'dropdown');
        $contract = [
            'multiple' => (bool)$this->multi,
            'optionsMode' => $this->getOptionsMode(),
            'options' => array_values(array_map(static function(array $option) {
                return [
                    'label' => $option['label'] ?? '',
                    'value' => $option['value'] ?? '',
                    'selected' => FieldOptionHelper::isOptionDefault($option),
                    'disabled' => self::isOptionFrontEndDisabled($option),
                ];
            }, $this->getFieldOptions())),
        ];

        if ($displayType === 'dropdown' && property_exists($this, 'placeholder')) {
            $contract['placeholder'] = $this->placeholder;
        }

        if (in_array($displayType, ['radio', 'checkboxes'], true)) {
            $contract['layout'] = $this->layout ?? 'vertical';
        }

        if ($displayType === 'checkboxes') {
            $contract['min'] = $this->min ?? null;
            $contract['max'] = $this->max ?? null;
        }

        return array_merge(parent::defineClientInput(), $contract);
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): string
    {
        if ($value instanceof MultiOptionFieldValue) {
            return implode(', ', $value->labels());
        }

        if ($value instanceof SingleOptionFieldValue) {
            return $value->getDisplayLabel();
        }

        return '';
    }

    protected function getPredefinedOptions(): array
    {
        return Formie::$plugin->getOptionSources()->getPredefinedOptions();
    }

    protected function defineOptionDynamicGeneralSchema(): array
    {
        return [
            SchemaHelper::optionDynamicSettingsField([
                'fieldType' => static::class,
                'label' => Craft::t('formie', 'Options'),
                'instructions' => Craft::t('formie', 'Define the available options for users to select from.'),
                'resolveAction' => 'formie/fields/resolve-option-source',
                'detachAction' => 'formie/fields/detach-option-source',
                'predefinedOptionsAction' => 'formie/fields/get-predefined-options',
                'predefinedProviders' => Formie::$plugin->getOptionSources()->getPredefinedProviderOptions(),
                'hasRegisteredOptionSources' => Formie::$plugin->getOptionSources()->hasRegisteredOptionSources(OptionSourceProviderHelper::USAGE_OPTIONS),
                'registeredConfigAction' => 'formie/fields/get-registered-option-source-config',
                'hasIntegrationOptionSources' => Formie::$plugin->getOptionSources()->hasIntegrationOptionSources(),
                'integrationConfigAction' => 'formie/fields/get-integration-option-source-config',
            ]),
        ];
    }

    protected function setPrePopulatedValue(mixed $value): mixed
    {
        if ($this->multi) {
            return explode(',', $value);
        }

        return parent::setPrePopulatedValue($value);
    }

    protected function translatedOptions(): array
    {
        $options = [];

        foreach ($this->getFieldOptions() as $option) {
            if (isset($option['optgroup'])) {
                $options[] = [
                    'optgroup' => $option['optgroup'],
                ];
            } else {
                $options[] = [
                    'label' => $option['label'],
                    'value' => (string)$option['value'],
                ];
            }
        }

        return $options;
    }

    protected function defaultValue(): array|string|null
    {
        if ($this->multi) {
            $defaultValues = [];

            foreach ($this->getResolvedOptions() as $option) {
                if (FieldOptionHelper::isOptionDefault($option)) {
                    $defaultValues[] = (string)$option['value'];
                }
            }

            return $defaultValues;
        }

        foreach ($this->getResolvedOptions() as $option) {
            if (FieldOptionHelper::isOptionDefault($option)) {
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
