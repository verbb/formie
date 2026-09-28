<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\models\Notification;
use verbb\formie\references\ReferenceCatalogue;
use verbb\formie\references\ReferenceContext;
use verbb\formie\references\ReferenceResolver;

use Craft;
use craft\helpers\Json;

use yii\base\Event;

use Throwable;

class Variables
{
    // Static Methods
    // =========================================================================

    /**
     * Returns the full variable config for the form builder: category config, labels, and order.
     * Consumed by the client to resolve variable picker options (static + form fields).
     * Includes labels/order for builder categories and for sub-groups (fieldsVariables, formVariables, etc.).
     * Form builder only shows token-based groups: Fields, Form, General, Users.
     * We do not expose "Email", "Number", "Plain Text", or "Calculations" as display categories;
     * those keys are only used to look up config (which static variables + field types to include).
     */
    public static function getFormBuilderVariableConfig(): array
    {
        $config = self::getCategoryConfig();

        $labels = [
            self::GROUP_FIELDS => Craft::t('formie', 'Fields'),
            self::GROUP_FORM => Craft::t('formie', 'Form'),
            self::GROUP_SUBMISSION => Craft::t('formie', 'Submission'),
            self::GROUP_SYSTEM => Craft::t('formie', 'System'),
            self::GROUP_CURRENT_TIME => Craft::t('formie', 'Current Time'),
            self::GROUP_ENVIRONMENT => Craft::t('formie', 'Environment'),
            self::GROUP_CURRENT_SITE => Craft::t('formie', 'Site'),
            self::GROUP_CURRENT_USER => Craft::t('formie', 'Users'),
            self::GROUP_DISPATCH => Craft::t('formie', 'Dispatch'),
            self::GROUP_CUSTOM => Craft::t('formie', 'Custom'),
            self::STATIC_FORM => Craft::t('formie', 'Form'),
            self::STATIC_GENERAL => Craft::t('formie', 'General'),
            self::STATIC_SITE => Craft::t('formie', 'Site'),
            self::STATIC_DISPATCH => Craft::t('formie', 'Dispatch'),
            self::STATIC_CUSTOM => Craft::t('formie', 'Custom'),
        ];

        $order = [
            self::GROUP_FIELDS,
            self::STATIC_FORM,
            self::STATIC_DISPATCH,
            self::STATIC_GENERAL,
            self::STATIC_CUSTOM,
            self::STATIC_SITE,
            self::GROUP_FORM,
            self::GROUP_SUBMISSION,
            self::GROUP_SYSTEM,
            self::GROUP_CURRENT_TIME,
            self::GROUP_ENVIRONMENT,
            self::GROUP_CURRENT_SITE,
            self::GROUP_CURRENT_USER,
        ];

        return [
            'variableCategoriesConfig' => $config,
            'variableCategoryLabels' => $labels,
            'variableCategoryOrder' => $order,
        ];
    }

    public static function getReportExportFilenameVariableConfig(): array
    {
        $categoryConfig = self::getCategoryConfig();
        $staticGroups = $categoryConfig['staticGroups'] ?? [];

        return [
            'variableCategories' => [
                'report' => [
                    self::_pickerSource(Craft::t('formie', 'Report Handle'), '{handle}'),
                    self::_pickerSource(Craft::t('formie', 'Report Name'), '{name}'),
                ],
                'general' => array_values(array_merge(
                    $staticGroups[self::GROUP_CURRENT_TIME] ?? [],
                    $staticGroups[self::GROUP_SYSTEM] ?? [],
                )),
                'site' => array_values($staticGroups[self::GROUP_CURRENT_SITE] ?? []),
            ],
            'variableCategoryLabels' => [
                'report' => Craft::t('formie', 'Report'),
                'general' => Craft::t('formie', 'General'),
                'site' => Craft::t('formie', 'Site'),
            ],
            'variableCategoryOrder' => ['report', 'general', 'site'],
            'variableTransformerRegistry' => $categoryConfig['transformerRegistry'] ?? [],
        ];
    }


    /**
     * Returns variable picker configuration used by variableConfig:
     * - staticGroups: grouped static variable catalogs
     * - groupAliases: macro groups (STATIC_*) expanded client-side
     * - transformerRegistry: available v1 transforms by value type
     */
    public static function getCategoryConfig(): array
    {
        return [
            'groupAliases' => [
                self::STATIC_FORM => [self::GROUP_FORM, self::GROUP_SUBMISSION, self::GROUP_DISPATCH],
                self::STATIC_DISPATCH => [self::GROUP_DISPATCH],
                self::STATIC_GENERAL => [self::GROUP_SYSTEM, self::GROUP_ENVIRONMENT, self::GROUP_CURRENT_TIME, self::GROUP_CUSTOM],
                self::STATIC_SITE => [self::GROUP_CURRENT_SITE, self::GROUP_CURRENT_USER],
                self::STATIC_CUSTOM => [self::GROUP_CUSTOM],
            ],
            'staticGroups' => (new ReferenceCatalogue())->pickerGroups(),
            'transformerRegistry' => self::_getTransformerRegistry(),
        ];
    }

    /**
     * Returns the merged list of variable definitions (with headings) for migrations/legacy use.
     */
    public static function getVariables(): array
    {
        $variables = [];
        $walk = static function(array $items) use (&$variables, &$walk): void {
            foreach ($items as $item) {
                if (isset($item['value'])) {
                    $variables[] = $item;
                }
                if (isset($item['children'])) {
                    $walk($item['children']);
                }
            }
        };
        foreach ((new ReferenceCatalogue())->pickerGroups() as $items) {
            $walk($items);
        }
        return $variables;
    }

    public static function getFieldForReference(string $refValue, Submission $submission): ?FieldInterface
    {
        $expression = References::parseReferenceExpression($refValue);
        return $expression->isValid && $expression->target === 'field'
            ? (new ReferenceResolver())->fieldFor($expression->identifier, ReferenceContext::forSubmission($submission))
            : null;
    }

    /**
     * Returns both the field (when the reference is a field reference) and the resolved value
     * with a single parse and single variables lookup. Use this when you need both to avoid
     * calling getFieldForReference() and References::parseValue() separately.
     */
    public static function getFieldAndValueForReference(string $refValue, Submission $submission, ?array $variables = null): array
    {
        $result = References::resolveValue($refValue, ReferenceContext::forSubmission($submission));
        return ['field' => $result->field, 'value' => $result->requireValue(), 'result' => $result];
    }

    public static function applyVariableTransformer(mixed $value, string $transformerId, array $params = []): mixed
    {
        // Applies a registered v1 transformer to a resolved variable value.
        // Public so that reference parsing and other callers can transform values consistently.
           
        switch ($transformerId) {
            case 'round':
            case 'floor':
            case 'ceil': {
                if (!is_numeric($value)) {
                    return $value;
                }

                $number = (float)$value;

                return match ($transformerId) {
                    'round' => round($number),
                    'floor' => floor($number),
                    'ceil' => ceil($number),
                    default => $number,
                };
            }

            case 'format': {
                if (is_numeric($value)) {
                    $decimals = isset($params['decimals']) && is_numeric($params['decimals']) ? (int)$params['decimals'] : 0;
                    $decimalPoint = isset($params['decimalPoint']) ? (string)$params['decimalPoint'] : '.';
                    $thousandsSeparator = isset($params['thousandsSeparator']) ? (string)$params['thousandsSeparator'] : ',';
                    return number_format((float)$value, $decimals, $decimalPoint, $thousandsSeparator);
                }

                $preset = isset($params['preset']) ? trim((string)$params['preset']) : '';
                $pattern = '';

                if ($preset === 'custom') {
                    $pattern = isset($params['pattern']) ? trim((string)$params['pattern']) : '';
                } else {
                    $pattern = self::_resolveDateFormatPatternFromPreset($preset);
                }

                if ($pattern === '') {
                    return $value;
                }

                try {
                    $date = \verbb\formie\fields\values\DateFieldValue::toDateTime($value);
                    return $date ? $date->format($pattern) : $value;
                } catch (Throwable) {
                    return $value;
                }
            }

            case 'lower':
            case 'upper':
            case 'title':
            case 'capitalize': {
                $text = self::_stringifyVariableValue($value);

                if ($transformerId === 'lower') {
                    return function_exists('mb_strtolower') ? mb_strtolower($text) : strtolower($text);
                }

                if ($transformerId === 'upper') {
                    return function_exists('mb_strtoupper') ? mb_strtoupper($text) : strtoupper($text);
                }

                if ($transformerId === 'title') {
                    return self::_toTitleCase($text);
                }

                if ($transformerId === 'capitalize') {
                    if ($text === '') {
                        return '';
                    }

                    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
                        $first = mb_substr($text, 0, 1);
                        $rest = mb_substr($text, 1);
                        return mb_strtoupper($first) . $rest;
                    }

                    return ucfirst($text);
                }
            }

            case 'replace': {
                $search = isset($params['search']) ? (string)$params['search'] : '';
                $replace = isset($params['replace']) ? (string)$params['replace'] : '';

                if ($search === '') {
                    return $value;
                }

                return str_replace($search, $replace, self::_stringifyVariableValue($value));
            }

            case 'truncate': {
                $text = self::_stringifyVariableValue($value);
                $length = isset($params['length']) && is_numeric($params['length']) ? max(1, (int)$params['length']) : 50;
                $suffix = isset($params['suffix']) ? (string)$params['suffix'] : '...';

                $textLength = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);

                if ($textLength <= $length) {
                    return $text;
                }

                $suffixLength = function_exists('mb_strlen') ? mb_strlen($suffix) : strlen($suffix);
                $take = max(0, $length - min($length, $suffixLength));
                $base = function_exists('mb_substr') ? mb_substr($text, 0, $take) : substr($text, 0, $take);

                return $base . $suffix;
            }

            case 'map': {
                $trueLabel = isset($params['trueLabel']) ? (string)$params['trueLabel'] : Craft::t('formie', 'Yes');
                $falseLabel = isset($params['falseLabel']) ? (string)$params['falseLabel'] : Craft::t('formie', 'No');
                return self::_toBoolean($value) ? $trueLabel : $falseLabel;
            }

            case 'join': {
                if (!is_array($value)) {
                    return $value;
                }

                $separator = isset($params['separator']) ? (string)$params['separator'] : ', ';

                return implode($separator, array_map(
                    static fn(mixed $item): string => self::_stringifyVariableValue($item),
                    $value,
                ));
            }

            case 'first': {
                if (!is_array($value)) {
                    return $value;
                }

                return $value[0] ?? null;
            }

            case 'last': {
                if (!is_array($value)) {
                    return $value;
                }

                return $value !== [] ? $value[array_key_last($value)] : null;
            }

            case 'count': {
                if (is_array($value)) {
                    return count($value);
                }

                return is_countable($value) ? count($value) : 0;
            }
        }

        return $value;
    }

    private static function _getTransformerRegistry(): array
    {
        $transformerRegistry = [
            self::TYPE_NUMBER => [
                [
                    'id' => 'round',
                    'label' => Craft::t('formie', 'Round'),
                    'description' => Craft::t('formie', 'Round to the nearest whole number.'),
                    'params' => [],
                ],
                [
                    'id' => 'floor',
                    'label' => Craft::t('formie', 'Floor'),
                    'description' => Craft::t('formie', 'Round down to the nearest whole number.'),
                    'params' => [],
                ],
                [
                    'id' => 'ceil',
                    'label' => Craft::t('formie', 'Ceil'),
                    'description' => Craft::t('formie', 'Round up to the nearest whole number.'),
                    'params' => [],
                ],
                [
                    'id' => 'format',
                    'label' => Craft::t('formie', 'Number Format'),
                    'description' => Craft::t('formie', 'Format numeric output with decimal precision and separators.'),
                    'params' => [
                        [
                            'name' => 'decimals',
                            'type' => 'number',
                            'label' => Craft::t('formie', 'Decimal Places'),
                            'required' => false,
                            'default' => 0,
                        ],
                        [
                            'name' => 'decimalPoint',
                            'type' => 'string',
                            'label' => Craft::t('formie', 'Decimal Separator'),
                            'required' => false,
                            'default' => '.',
                        ],
                        [
                            'name' => 'thousandsSeparator',
                            'type' => 'string',
                            'label' => Craft::t('formie', 'Thousands Separator'),
                            'required' => false,
                            'default' => ',',
                        ],
                    ],
                ],
            ],
            self::TYPE_TEXT => [
                [
                    'id' => 'lower',
                    'label' => Craft::t('formie', 'Lowercase'),
                    'description' => Craft::t('formie', 'Convert text to lowercase.'),
                    'params' => [],
                ],
                [
                    'id' => 'upper',
                    'label' => Craft::t('formie', 'Uppercase'),
                    'description' => Craft::t('formie', 'Convert text to uppercase.'),
                    'params' => [],
                ],
                [
                    'id' => 'title',
                    'label' => Craft::t('formie', 'Title Case'),
                    'description' => Craft::t('formie', 'Convert text to title case.'),
                    'params' => [],
                ],
                [
                    'id' => 'capitalize',
                    'label' => Craft::t('formie', 'Capitalize First Letter'),
                    'description' => Craft::t('formie', 'Capitalize only the first letter of the text.'),
                    'params' => [],
                ],
                [
                    'id' => 'replace',
                    'label' => Craft::t('formie', 'Replace'),
                    'description' => Craft::t('formie', 'Replace part of the text with another value.'),
                    'params' => [
                        [
                            'name' => 'search',
                            'type' => 'string',
                            'label' => Craft::t('formie', 'Search'),
                            'required' => true,
                        ],
                        [
                            'name' => 'replace',
                            'type' => 'string',
                            'label' => Craft::t('formie', 'Replace With'),
                            'required' => false,
                            'default' => '',
                        ],
                    ],
                ],
                [
                    'id' => 'truncate',
                    'label' => Craft::t('formie', 'Truncate'),
                    'description' => Craft::t('formie', 'Truncate text to a maximum length.'),
                    'params' => [
                        [
                            'name' => 'length',
                            'type' => 'number',
                            'label' => Craft::t('formie', 'Length'),
                            'required' => true,
                            'default' => 50,
                        ],
                        [
                            'name' => 'suffix',
                            'type' => 'string',
                            'label' => Craft::t('formie', 'Suffix'),
                            'required' => false,
                            'default' => '...',
                        ],
                    ],
                ],
            ],
            self::TYPE_DATE => [
                [
                    'id' => 'format',
                    'label' => Craft::t('formie', 'Date Format'),
                    'description' => Craft::t('formie', 'Format date/time output with a Twig date format string.'),
                    'params' => [
                        [
                            'name' => 'preset',
                            'type' => 'string',
                            'label' => Craft::t('formie', 'Format'),
                            'required' => true,
                            'default' => 'isoDate',
                            'options' => [
                                [
                                    'value' => 'datetimeUs12',
                                    'label' => Craft::t('formie', 'Date/Time (mm/dd/yyyy 12h)'),
                                    'group' => Craft::t('formie', 'Date/Time'),
                                ],
                                [
                                    'value' => 'datetimeEu12',
                                    'label' => Craft::t('formie', 'Date/Time (dd/mm/yyyy 12h)'),
                                    'group' => Craft::t('formie', 'Date/Time'),
                                ],
                                [
                                    'value' => 'datetimeEu24',
                                    'label' => Craft::t('formie', 'Date/Time (dd/mm/yyyy 24h)'),
                                    'group' => Craft::t('formie', 'Date/Time'),
                                ],
                                [
                                    'value' => 'datetimeIso24',
                                    'label' => Craft::t('formie', 'Date/Time (yyyy-mm-dd 24h)'),
                                    'group' => Craft::t('formie', 'Date/Time'),
                                ],
                                [
                                    'value' => 'dateUs',
                                    'label' => Craft::t('formie', 'Date (mm/dd/yyyy)'),
                                    'group' => Craft::t('formie', 'Date'),
                                ],
                                [
                                    'value' => 'dateEu',
                                    'label' => Craft::t('formie', 'Date (dd/mm/yyyy)'),
                                    'group' => Craft::t('formie', 'Date'),
                                ],
                                [
                                    'value' => 'isoDate',
                                    'label' => Craft::t('formie', 'Date (yyyy-mm-dd)'),
                                    'group' => Craft::t('formie', 'Date'),
                                ],
                                [
                                    'value' => 'dateLong',
                                    'label' => Craft::t('formie', 'Date (Month Day, Year)'),
                                    'group' => Craft::t('formie', 'Date'),
                                ],
                                [
                                    'value' => 'time12',
                                    'label' => Craft::t('formie', 'Time (12h)'),
                                    'group' => Craft::t('formie', 'Time'),
                                ],
                                [
                                    'value' => 'time24',
                                    'label' => Craft::t('formie', 'Time (24h)'),
                                    'group' => Craft::t('formie', 'Time'),
                                ],
                            ],
                        ],
                        [
                            'name' => 'pattern',
                            'type' => 'string',
                            'label' => Craft::t('formie', 'Custom Format Pattern'),
                            'required' => true,
                            'placeholder' => Craft::t('formie', 'e.g. Y-m-d H:i'),
                            'showWhen' => [
                                'param' => 'preset',
                                'equals' => 'custom',
                            ],
                        ],
                    ],
                ],
            ],
            'boolean' => [
                [
                    'id' => 'map',
                    'label' => Craft::t('formie', 'True/False Labels'),
                    'description' => Craft::t('formie', 'Map boolean values to custom labels.'),
                    'params' => [
                        [
                            'name' => 'trueLabel',
                            'type' => 'string',
                            'label' => Craft::t('formie', 'True Label'),
                            'required' => false,
                            'default' => Craft::t('formie', 'Yes'),
                        ],
                        [
                            'name' => 'falseLabel',
                            'type' => 'string',
                            'label' => Craft::t('formie', 'False Label'),
                            'required' => false,
                            'default' => Craft::t('formie', 'No'),
                        ],
                    ],
                ],
            ],
            self::TYPE_ARRAY => [
                [
                    'id' => 'join',
                    'label' => Craft::t('formie', 'Join'),
                    'description' => Craft::t('formie', 'Join multiple values into a list string.'),
                    'params' => [
                        [
                            'name' => 'separator',
                            'type' => 'string',
                            'label' => Craft::t('formie', 'Separator'),
                            'required' => false,
                            'default' => ', ',
                        ],
                    ],
                ],
                [
                    'id' => 'first',
                    'label' => Craft::t('formie', 'First'),
                    'description' => Craft::t('formie', 'Use the first value from a list.'),
                    'params' => [],
                ],
                [
                    'id' => 'last',
                    'label' => Craft::t('formie', 'Last'),
                    'description' => Craft::t('formie', 'Use the last value from a list.'),
                    'params' => [],
                ],
                [
                    'id' => 'count',
                    'label' => Craft::t('formie', 'Count'),
                    'description' => Craft::t('formie', 'Count the number of values in a list.'),
                    'params' => [],
                ],
            ],
        ];

        $registry = self::_sanitizeTransformerRegistry($transformerRegistry);
        foreach ((new ReferenceCatalogue())->pickerTransforms() as $type => $transforms) {
            $registry[$type] = [...($registry[$type] ?? []), ...$transforms];
        }
        return $registry;
    }

    private static function _sanitizeTransformerRegistry(mixed $registry): array
    {
        if (!is_array($registry)) {
            return [];
        }

        $sanitized = [];

        foreach ($registry as $valueType => $definitions) {
            if (!is_string($valueType) || trim($valueType) === '' || !is_array($definitions)) {
                self::_logInvalidTransformerEntry('Ignoring invalid transformer registry group entry.', [
                    'valueType' => $valueType,
                ]);
                continue;
            }

            $group = [];
            $seenIds = [];

            foreach ($definitions as $definition) {
                $normalized = self::_sanitizeTransformerDefinition($definition);
                if ($normalized === null) {
                    continue;
                }

                if (isset($seenIds[$normalized['id']])) {
                    self::_logInvalidTransformerEntry('Ignoring duplicate transformer id within valueType group.', [
                        'valueType' => $valueType,
                        'id' => $normalized['id'],
                    ]);
                    continue;
                }

                $seenIds[$normalized['id']] = true;
                $group[] = $normalized;
            }

            if ($group !== []) {
                $sanitized[$valueType] = $group;
            }
        }

        return $sanitized;
    }

    private static function _sanitizeTransformerDefinition(mixed $definition): ?array
    {
        if (!is_array($definition)) {
            self::_logInvalidTransformerEntry('Ignoring invalid transformer definition (expected array).');
            return null;
        }

        $id = trim((string)($definition['id'] ?? ''));
        $label = trim((string)($definition['label'] ?? ''));
        $description = trim((string)($definition['description'] ?? ''));
        $params = $definition['params'] ?? [];
        $appliesTo = $definition['appliesTo'] ?? [];

        if ($id === '' || $label === '') {
            self::_logInvalidTransformerEntry('Ignoring invalid transformer definition (missing id/label).', [
                'id' => $id,
                'label' => $label,
            ]);
            return null;
        }

        if (!is_array($params)) {
            $params = [];
        }

        if (!is_array($appliesTo)) {
            $appliesTo = [];
        }

        $normalizedParams = [];

        foreach ($params as $param) {
            $normalized = self::_sanitizeTransformerParam($param);

            if ($normalized === null) {
                self::_logInvalidTransformerEntry('Ignoring invalid transformer param entry.', [
                    'transformerId' => $id,
                ]);

                continue;
            }

            $normalizedParams[] = $normalized;
        }

        return [
            'id' => $id,
            'label' => $label,
            'description' => $description,
            'params' => $normalizedParams,
            'appliesTo' => array_values(array_unique(array_filter(array_map('strval', $appliesTo)))),
        ];
    }

    private static function _sanitizeTransformerParam(mixed $param): ?array
    {
        if (!is_array($param)) {
            return null;
        }

        $name = trim((string)($param['name'] ?? ''));
        $type = trim((string)($param['type'] ?? 'string'));
        $label = trim((string)($param['label'] ?? ''));
        $required = (bool)($param['required'] ?? false);

        if ($name === '' || $label === '') {
            return null;
        }

        if (!in_array($type, ['string', 'number', 'boolean'], true)) {
            $type = 'string';
        }

        $normalized = [
            'name' => $name,
            'type' => $type,
            'label' => $label,
            'required' => $required,
        ];

        if (array_key_exists('default', $param)) {
            $normalized['default'] = $param['default'];
        }

        if (array_key_exists('placeholder', $param)) {
            $normalized['placeholder'] = (string)$param['placeholder'];
        }

        $options = self::_sanitizeTransformerParamOptions($param['options'] ?? null);

        if ($options !== []) {
            $normalized['options'] = $options;
        }

        $showWhen = self::_sanitizeTransformerParamShowWhen($param['showWhen'] ?? null);

        if ($showWhen !== null) {
            $normalized['showWhen'] = $showWhen;
        }

        return $normalized;
    }

    private static function _sanitizeTransformerParamOptions(mixed $options): array
    {
        if (!is_array($options)) {
            return [];
        }

        $normalized = [];

        foreach ($options as $option) {
            if (!is_array($option)) {
                continue;
            }

            $value = isset($option['value']) ? (string)$option['value'] : '';
            $label = isset($option['label']) ? (string)$option['label'] : '';

            if ($value === '' || $label === '') {
                continue;
            }

            $entry = [
                'value' => $value,
                'label' => $label,
            ];

            if (isset($option['group']) && trim((string)$option['group']) !== '') {
                $entry['group'] = (string)$option['group'];
            }

            $normalized[] = $entry;
        }

        return $normalized;
    }

    private static function _sanitizeTransformerParamShowWhen(mixed $showWhen): ?array
    {
        if (!is_array($showWhen)) {
            return null;
        }

        $param = isset($showWhen['param']) ? trim((string)$showWhen['param']) : '';
        $equals = isset($showWhen['equals']) ? (string)$showWhen['equals'] : '';

        if ($param === '') {
            return null;
        }

        return [
            'param' => $param,
            'equals' => $equals,
        ];
    }

    private static function _logInvalidTransformerEntry(string $message, array $context = []): void
    {
        $contextSuffix = $context === [] ? '' : ' ' . Json::encode($context);

        Craft::warning($message . $contextSuffix, __METHOD__);
    }


    public static function getSummaryVariables(Submission $submission, Notification $notification): array
    {
        $allFields = [];
        $allContentFields = [];
        $allVisibleFields = [];

        // Build expensive summary variables used by multi-line content references.
        foreach ($submission->getFields() as $field) {
            if (!$field->includeInEmailFieldSummaries || $field->isConditionallyHidden($submission)) {
                continue;
            }

            $value = $submission->getFieldValue($field->valueKey());

            $allFields[] = $field;

            if (!$field->isValueEmpty($value, $submission)) {
                $allContentFields[] = $field;
            }

            if (!$field->getIsHidden()) {
                $allVisibleFields[] = $field;
            }
        }

        return [
            'allFields' => self::_renderSummaryTemplate($notification, $submission, 'all-fields', $allFields),
            'allContentFields' => self::_renderSummaryTemplate($notification, $submission, 'all-content-fields', $allContentFields),
            'allVisibleFields' => self::_renderSummaryTemplate($notification, $submission, 'all-visible-fields', $allVisibleFields),
        ];
    }

    private static function _renderSummaryTemplate(Notification $notification, Submission $submission, string $template, array $fields): string
    {
        if (!$fields) {
            return '';
        }

        $html = $notification->renderTemplate($template, [
            'notification' => $notification,
            'submission' => $submission,
            'fields' => $fields,
        ]);

        return StringHelper::cleanString(html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private static function _resolveDateFormatPatternFromPreset(string $preset): string
    {
        return match ($preset) {
            'datetimeUs12' => 'm/d/Y h:i A',
            'datetimeEu12' => 'd/m/Y h:i A',
            'datetimeEu24' => 'd/m/Y H:i',
            'datetimeIso24' => 'Y-m-d H:i',
            'dateUs' => 'm/d/Y',
            'dateEu' => 'd/m/Y',
            'isoDate' => 'Y-m-d',
            'dateLong' => 'F j, Y',
            'time12' => 'h:i A',
            'time24' => 'H:i',
            default => '',
        };
    }

    private static function _stringifyVariableValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        if ($value instanceof \Stringable) {
            return (string)$value;
        }

        return '';
    }

    private static function _toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float)$value !== 0.0;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if ($normalized === '' || in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }

            return true;
        }

        return (bool)$value;
    }

    private static function _toTitleCase(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (defined('MB_CASE_TITLE') && function_exists('mb_convert_case')) {
            return mb_convert_case($value, MB_CASE_TITLE);
        }

        return ucwords(strtolower($value));
    }


    // Constants
    // =========================================================================

    public const TARGET_CUSTOM = 'custom';
    public const TYPE_TEXT = 'text';
    public const TYPE_EMAIL = 'email';
    public const TYPE_NUMBER = 'number';
    public const TYPE_CALCULATIONS = 'calculations';
    public const TYPE_URL = 'url';
    public const TYPE_DATE = 'date';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_ARRAY = 'array';

    public const GROUP_FIELDS = 'fieldsVariables';
    public const GROUP_FORM = 'formVariables';
    public const GROUP_SUBMISSION = 'submissionVariables';
    public const GROUP_SYSTEM = 'systemVariables';
    public const GROUP_CURRENT_TIME = 'currentTimeVariables';
    public const GROUP_ENVIRONMENT = 'environmentVariables';
    public const GROUP_CURRENT_SITE = 'siteVariables';
    public const GROUP_CURRENT_USER = 'userVariables';
    public const GROUP_DISPATCH = 'dispatchVariables';
    public const GROUP_CUSTOM = 'customVariables';

    public const STATIC_FIELDS = self::GROUP_FIELDS;
    public const STATIC_FORM = 'staticFormVariables';
    public const STATIC_GENERAL = 'staticGeneralVariables';
    public const STATIC_SITE = 'staticSiteVariables';
    public const STATIC_DISPATCH = 'staticDispatchVariables';
    public const STATIC_CUSTOM = 'staticCustomVariables';

}
