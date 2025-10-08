<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\ElementFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;

use Craft;
use craft\fields\BaseRelationField;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Throwable;

class ConditionsHelper
{
    // Static Methods
    // =========================================================================

    public static function getEvaluator(): ExpressionLanguage
    {
        $expressionLanguage = new ExpressionLanguage();

        // Add custom evaluation rules
        $expressionLanguage->register('contains', function() {
        }, function($args, $subject, $pattern) {
            if (is_array($subject)) {
                return in_array($pattern, $subject);
            }

            return StringHelper::contains((string)$subject, $pattern);
        });

        $expressionLanguage->register('notContains', function() {
        }, function($args, $subject, $pattern) {
            if (is_array($subject)) {
                return !in_array($pattern, $subject);
            }

            return !StringHelper::contains((string)$subject, $pattern);
        });

        $expressionLanguage->register('startsWith', function() {
        }, function($args, $subject, $pattern) {
            return str_starts_with((string)$subject, $pattern);
        });

        $expressionLanguage->register('endsWith', function() {
        }, function($args, $subject, $pattern) {
            return StringHelper::endsWith((string)$subject, $pattern);
        });

        $expressionLanguage->register('empty', function() {
        }, function($args, $subject) {
            if (is_null($subject)) {
                return true;
            }

            if (is_string($subject) && trim($subject) === '') {
                return true;
            }

            if (is_array($subject) && empty($subject)) {
                return true;
            }

            if (is_object($subject) && empty((array)$subject)) {
                return true;
            }

            return false;
        });

        $expressionLanguage->register('notEmpty', function() {
        }, function($args, $subject) {
            if (is_null($subject)) {
                return false;
            }

            if (is_string($subject) && trim($subject) === '') {
                return false;
            }

            if (is_array($subject) && empty($subject)) {
                return false;
            }

            if (is_object($subject) && empty((array)$subject)) {
                return false;
            }

            return true;
        });

        return $expressionLanguage;
    }

    public static function getCondition(string $condition): string
    {
        // Handle some settings defined in JS, so they're compatible with the evaluator we're using.
        // FYI, mostly for backward compatibility with `hoa/ruler` conditions.
        if ($condition === '=') {
            return '==';
        }

        return $condition;
    }

    public static function getRule(string $condition): string
    {
        // Convert condition set via JS into ruler-compatible
        $operator = ConditionsHelper::getCondition($condition);

        // For custom rules, we need a custom syntax. Symfony doesn't support custom operators, which would be nice
        // Instead of `field contains value` we need to do `contains(field, value)`.
        if (in_array($operator, ['contains', 'notContains', 'startsWith', 'endsWith', 'empty', 'notEmpty'])) {
            return "{$operator}(field, value)";
        }

        return "field {$operator} value";
    }

    public static function evaluateConditions(array $conditions, Submission $submission, $callback = null): array
    {
        $results = [];
        $evaluator = ConditionsHelper::getEvaluator();

        foreach ($conditions as $condition) {
            try {
                // Variables to pass into the evaluator for rules to use
                $variables = [
                    'field' => $condition['field'],
                    'value' => $condition['value'],
                ];

                // Protect against empty conditions
                if (!trim(ArrayHelper::recursiveImplode($variables, ''))) {
                    continue;
                }

                // Check to see if this is a custom field, or an attribute on the submission
                if (str_starts_with($variables['field'], '{submission:')) {
                    $variables['field'] = str_replace(['{submission:', '}'], ['', ''], $variables['field']);

                    // DOn't use `ArrayHelper::getValue()` to allow getters to take over
                    $variables['field'] = $submission->{$variables['field']};
                } else {
                    $variables['field'] = str_replace(['{field:', '}'], ['', ''], $variables['field']);

                    // Fetch the value, serialized for string comparison
                    $serializedFieldValue = ConditionsHelper::getSerializedFieldValues($submission, [$variables['field']]);

                    // Parse the field handle first to get the submission value
                    $variables['field'] = ArrayHelper::getValue($serializedFieldValue, $variables['field']);
                }

                // Special-case for some fields, that support multiple values (mutli-select, checkboxes)
                // where we actually want to do a 'contains' lookup for arrays if we're doing equality operators.
                if (is_array($variables['field'])) {
                    // Check to see if we're using equality operators. Technically, we want to do a contains
                    // not-contains lookup because we're dealing with arrays. For all other cases (startswith,
                    // contains) we still want to do string-based checks, so ensure the value is a string.
                    //
                    // For instance, to check if `[1,2] = 1` we switch that to `[1,2] contains 1`.
                    // For `[1,2] contains 1`, we switch to `1 2 contains 1`
                    if ($condition['condition'] === '=') {
                        $condition['condition'] = 'contains';
                    } else if ($condition['condition'] === '!=') {
                        $condition['condition'] = 'notContains';
                    } else {
                        $variables['field'] = ArrayHelper::recursiveImplode($variables['field'], ' ');
                    }
                }

                // Create the rule for the evaluator - some rules need special syntax
                $rule = ConditionsHelper::getRule($condition['condition']);

                // Check to see how we need to return results. By default, just a true/false on whether passed
                $result = $evaluator->evaluate($rule, $variables);

                // Allow a callback to define how to return the result
                if ($callback) {
                    if ($callbackResult = $callback($result, $condition)) {
                        $results[] = $callbackResult;
                    }
                } else {
                    $results[] = $result;
                }
            } catch (Throwable $e) {
                Formie::error('Failed to parse conditional “{rule}”: “{message}” {file}:{line}', [
                    'rule' => trim(ArrayHelper::recursiveImplode($condition, '')),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                continue;
            }
        }

        return $results;
    }

    public static function getConditionalTestResult(array $conditionSettings, Submission $submission): bool
    {
        $conditions = $conditionSettings['conditions'] ?? [];

        $results = ConditionsHelper::evaluateConditions($conditions, $submission);

        // Check to see how to compare the result (any or all).
        if ($conditionSettings['conditionRule'] === 'all') {
            // Are _all_ the conditions the same?
            return (bool)array_product($results);
        }

        return in_array(true, $results);
    }

    public static function getSerializedFieldValues(Submission $submission, array $includedHandles = []): array
    {
        $serializedValues = [];

        // Some handles will be complex, so only grab the first portion as the field handle
        $handles = array_filter(array_map(function($item) {
            return explode('.', $item)[0] ?? null;
        }, $includedHandles));

        foreach ($submission->getFields() as $field) {
            if ($field->getIsCosmetic()) {
                continue;
            }

            // If we only want specific fields, no need to parse them all to improve performance
            if ($handles && !in_array($field->handle, $handles)) {
                continue;
            }

            $value = $submission->getFieldValue($field->fieldKey);

            $serializedValues[$field->handle] = $field->getValueForCondition($value, $submission);
        }

        return $serializedValues;
    }

    public static function prepConditionsForJs(array $conditions, string $namespace = 'fields'): array
    {
        // Prep the conditions for JS
        foreach ($conditions as &$condition) {
            ArrayHelper::remove($condition, 'id');

            // Dot-notation to name input syntax
            $condition['field'] = $namespace . '[' . str_replace(['{field:', '}', '.'], ['', '', ']['], $condition['field']) . ']';
        }

        return $conditions;
    }
}
