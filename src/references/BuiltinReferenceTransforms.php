<?php
namespace verbb\formie\references;

use verbb\formie\fields\values\DateFieldValue;
use verbb\formie\helpers\Variables;

use Craft;

use Stringable;
use Throwable;

final class BuiltinReferenceTransforms
{
    // Static Methods
    // =========================================================================

    public static function parameters(string $id): ?array
    {
        static $parameters;

        if ($parameters === null) {
            $parameters = [];

            foreach (self::pickerDefinitions() as $definitions) {
                foreach ($definitions as $definition) {
                    $key = $definition['id'];
                    $parameters[$key] = array_values(array_unique([...($parameters[$key] ?? []), ...array_column($definition['params'], 'name')]));
                }
            }
        }
        return $parameters[$id] ?? null;
    }

    public static function apply(mixed $value, string $transformerId, array $params = []): mixed
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
                    $date = DateFieldValue::toDateTime($value);
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

    public static function pickerDefinitions(): array
    {
        $transformerRegistry = [
            Variables::TYPE_NUMBER => [
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
            Variables::TYPE_TEXT => [
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
            Variables::TYPE_DATE => [
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
            Variables::TYPE_ARRAY => [
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

        foreach ($transformerRegistry as &$definitions) {
            foreach ($definitions as &$definition) {
                $definition['appliesTo'] = [];
            }
        }
        return $transformerRegistry;
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

        if ($value instanceof Stringable) {
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

}
