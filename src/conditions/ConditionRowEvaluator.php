<?php
namespace verbb\formie\conditions;

use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;

class ConditionRowEvaluator
{
    // Properties
    // =========================================================================

    private ConditionValueResolver $valueResolver;


    // Public Methods
    // =========================================================================

    public function __construct(ConditionValueResolver $valueResolver)
    {
        $this->valueResolver = $valueResolver;
    }

    public function evaluate(array $condition, Submission $submission): bool
    {
        $subject = $this->valueResolver->resolveFieldReferenceValue($condition['field'] ?? null, $submission);
        $operator = (string)($condition['condition'] ?? '');
        $expected = $condition['value'] ?? null;

        if ($operator === '' || !ConditionOperator::isSupported($operator)) {
            return false;
        }

        if (is_array($subject)) {
            // Authors usually mean "contains this selected option" when they
            // write equals/not-equals against checkbox- or relation-style data.
            // Preserve that intent instead of requiring separate operators for
            // the common multi-value case.
            if ($operator === ConditionOperator::EQ) {
                $operator = ConditionOperator::CONTAINS;
            } else if ($operator === ConditionOperator::NEQ) {
                $operator = ConditionOperator::NOT_CONTAINS;
            } else {
                $subject = ArrayHelper::recursiveImplode($subject, ' ');
            }
        }

        if ($operator === ConditionOperator::EQ) {
            return $this->_equals($subject, $expected);
        }

        if ($operator === ConditionOperator::NEQ) {
            return !$this->_equals($subject, $expected);
        }

        if ($operator === ConditionOperator::GT) {
            return $this->_compareOrder($subject, $expected) > 0;
        }

        if ($operator === ConditionOperator::LT) {
            return $this->_compareOrder($subject, $expected) < 0;
        }

        if ($operator === ConditionOperator::CONTAINS) {
            return $this->_contains($subject, $expected);
        }

        if ($operator === ConditionOperator::NOT_CONTAINS) {
            return !$this->_contains($subject, $expected);
        }

        if ($operator === ConditionOperator::STARTS_WITH) {
            return str_starts_with((string)$subject, (string)$expected);
        }

        if ($operator === ConditionOperator::ENDS_WITH) {
            return StringHelper::endsWith((string)$subject, (string)$expected);
        }

        if ($operator === ConditionOperator::EMPTY) {
            return $this->_isEmpty($subject);
        }

        if ($operator === ConditionOperator::NOT_EMPTY) {
            return !$this->_isEmpty($subject);
        }

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _contains(mixed $subject, mixed $expected): bool
    {
        if (is_array($subject)) {
            foreach ($subject as $item) {
                if ($this->_equals($item, $expected)) {
                    return true;
                }
            }

            return false;
        }

        return StringHelper::contains($this->_stringifyComparable($subject), $this->_stringifyComparable($expected));
    }

    private function _isEmpty(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        if (is_array($value) && empty($value)) {
            return true;
        }

        if (is_object($value) && empty((array)$value)) {
            return true;
        }

        return false;
    }

    private function _equals(mixed $subject, mixed $expected): bool
    {
        $booleanSubject = $this->_normalizeBooleanComparable($subject);
        $booleanExpected = $this->_normalizeBooleanComparable($expected);

        if ($booleanSubject !== null && $booleanExpected !== null) {
            return $booleanSubject === $booleanExpected;
        }

        $numericSubject = $this->_normalizeNumericComparable($subject);
        $numericExpected = $this->_normalizeNumericComparable($expected);

        if ($numericSubject !== null && $numericExpected !== null) {
            return $numericSubject === $numericExpected;
        }

        return $this->_stringifyComparable($subject) === $this->_stringifyComparable($expected);
    }

    private function _compareOrder(mixed $subject, mixed $expected): int
    {
        $numericSubject = $this->_normalizeNumericComparable($subject);
        $numericExpected = $this->_normalizeNumericComparable($expected);

        if ($numericSubject !== null && $numericExpected !== null) {
            return $numericSubject <=> $numericExpected;
        }

        return strcmp($this->_stringifyComparable($subject), $this->_stringifyComparable($expected));
    }

    private function _normalizeBooleanComparable(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            if ($value === 1 || $value === 1.0) {
                return true;
            }

            if ($value === 0 || $value === 0.0) {
                return false;
            }

            return null;
        }

        if (!is_string($value)) {
            return null;
        }

        return match (strtolower(trim($value))) {
            'true', '1', 'yes', 'on' => true,
            'false', '0', 'no', 'off' => false,
            default => null,
        };
    }

    private function _normalizeNumericComparable(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float)$value;
        }

        if (!is_string($value)) {
            return null;
        }

        $trimmedValue = trim($value);

        if ($trimmedValue === '' || !is_numeric($trimmedValue)) {
            return null;
        }

        return (float)$trimmedValue;
    }

    private function _stringifyComparable(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string)$value;
        }

        return '';
    }
}
