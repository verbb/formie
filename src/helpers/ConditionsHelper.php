<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\fields\formfields\Group;

use Craft;
use craft\fields\BaseRelationField;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ConditionsHelper
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function getEvaluator()
    {
        $expressionLanguage = new ExpressionLanguage();

        // Add custom evaluation rules
        $expressionLanguage->register('contains', function() {}, function($args, $subject, $pattern) {
            if (is_array($subject)) {
                return in_array($pattern, $subject);
            } else {
                return StringHelper::contains((string)$subject, $pattern);
            }
        });

        $expressionLanguage->register('notContains', function() {}, function($args, $subject, $pattern) {
            if (is_array($subject)) {
                return !in_array($pattern, $subject);
            } else {
                return !StringHelper::contains((string)$subject, $pattern);
            }
        });

        $expressionLanguage->register('startsWith', function() {}, function($args, $subject, $pattern) {
            return StringHelper::startsWith((string)$subject, $pattern);
        });

        $expressionLanguage->register('endsWith', function() {}, function($args, $subject, $pattern) {
            return StringHelper::endsWith((string)$subject, $pattern);
        });

        return $expressionLanguage;
    }

    /**
     * @inheritDoc
     */
    public static function getCondition($condition)
    {   
        // Handle some settings defined in JS, so they're compatible with the evaluator we're using.
        // FYI, mostly for backward compatibility with `hoa/ruler` conditions.
        if ($condition === '=') {
            return '==';
        }

        return $condition;
    }

    /**
     * @inheritDoc
     */
    public static function getRule($condition)
    {
        // Convert condition set via JS into ruler-compatible
        $operator = ConditionsHelper::getCondition($condition);

        // For custom rules, we need a custom syntax. Symfony doesn't support custom operators, which would be nice
        // Instead of `field contains value` we need to do `contains(field, value)`.
        if (in_array($operator, ['contains', 'notContains', 'startsWith', 'endsWith'])) {
            return "{$operator}(field, value)";
        }

        return "field {$operator} value";
    }

    /**
     * @inheritDoc
     */
    public static function evaluateConditions($conditions, $submission, $callback = null)
    {
        $results = [];
        $evaluator = ConditionsHelper::getEvaluator();

        // Fetch the values, serialized for string comparison
        $serializedFieldValues = ConditionsHelper::getSerializedFieldValues($submission);

        foreach ($conditions as $condition) {
            try {
                // Variables to pass into the evaluator for rules to use
                $variables = [
                    'field' => $condition['field'],
                    'value' => $condition['value'],
                ];

                $variables['field'] = str_replace(['{', '}'], ['', ''], $variables['field']);

                // Check to see if this is a custom field, or an attribute on the submission
                if (StringHelper::startsWith($variables['field'], 'submission:')) {
                    $variables['field'] = str_replace('submission:', '', $variables['field']);

                    $variables['field'] = ArrayHelper::getValue($submission, $variables['field']);
                } else {
                    // Parse the field handle first to get the submission value
                    $variables['field'] = ArrayHelper::getValue($serializedFieldValues, $variables['field']);
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
                        $variables['field'] = ConditionsHelper::recursiveImplode(' ', $variables['field']);
                    }
                }

                // Protect against empty conditions
                if (!trim(ConditionsHelper::recursiveImplode('', $variables))) {
                    continue;
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
            } catch (\Throwable $e) {
                Formie::error(Craft::t('formie', 'Failed to parse conditional “{rule}”: “{message}” {file}:{line}', [
                    'rule' => trim(ConditionsHelper::recursiveImplode('', $condition)),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]));

                continue;
            }
        }

        return $results;
    }

    /**
     * @inheritDoc
     */
    public static function getConditionalTestResult($conditionSettings, $submission)
    {
        $conditions = $conditionSettings['conditions'] ?? [];

        $results = ConditionsHelper::evaluateConditions($conditions, $submission);
        $result = false;

        // Check to see how to compare the result (any or all).
        if ($conditionSettings['conditionRule'] === 'all') {
            // Are _all_ the conditions the same?
            $result = (bool)array_product($results);
        } else {
            $result = (bool)in_array(true, $results);
        }

        return $result;
    }

    /**
     * Recursively implodes an array with optional key inclusion
     * 
     * Example of $include_keys output: key, value, key, value, key, value
     * 
     * @access  public
     * @param   array   $array         multi-dimensional array to recursively implode
     * @param   string  $glue          value that glues elements together   
     * @param   bool    $include_keys  include keys before their values
     * @param   bool    $trim_all      trim ALL whitespace from string
     * @return  string  imploded array
     */ 
    public static function recursiveImplode($glue = ',', array $array, $include_keys = false, $trim_all = false)
    {
        $glued_string = '';

        // Recursively iterates array and adds key/value to glued string
        array_walk_recursive($array, function($value, $key) use ($glue, $include_keys, &$glued_string) {
            $include_keys && $glued_string .= $key.$glue;
            $glued_string .= $value.$glue;
        });

        // Removes last $glue from string
        strlen($glue) > 0 && $glued_string = substr($glued_string, 0, -strlen($glue));

        // Trim ALL whitespace
        $trim_all && $glued_string = preg_replace("/(\s)/ixsm", '', $glued_string);

        return (string)$glued_string;
    }

    /**
     * @inheritdoc
     */
    public static function getSerializedFieldValues($submission): array
    {
        $serializedValues = [];

        if ($fieldLayout = $submission->getFieldLayout()) {
            foreach ($fieldLayout->getFields() as $field) {
                $value = $submission->getFieldValue($field->handle);

                // Special-handling for element fields which for integrations contain their titles
                // (or field setting labels), but we want IDs.
                if ($field instanceof BaseRelationField) {
                    $value = $field->serializeValue($value, $submission);
                } else if ($field instanceof Group) {
                    // Handling for Group fields who have a particular structure
                    $rows = array_values($field->serializeValue($value, $submission))[0] ?? [];

                    $value = ['rows' => ['new1' => $rows]];
                } else if (method_exists($field, 'serializeValueForIntegration')) {
                    $value = $field->serializeValueForIntegration($value, $submission);
                } else {
                    $value = $field->serializeValue($value, $submission);
                }

                $serializedValues[$field->handle] = $value;
            }
        }

        return $serializedValues;
    }
}
