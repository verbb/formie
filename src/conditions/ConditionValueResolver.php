<?php
namespace verbb\formie\conditions;

use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\References;
use verbb\formie\helpers\Variables;

class ConditionValueResolver
{
    // Public Methods
    // =========================================================================

    public function resolveFieldReferenceValue(mixed $fieldReference, Submission $submission): mixed
    {
        if (!is_string($fieldReference)) {
            return $fieldReference;
        }

        $fieldReference = trim($fieldReference);

        if ($fieldReference === '') {
            return '';
        }

        $expression = References::parseReferenceExpression($fieldReference);

        if ($expression->isValid) {
            if ($expression->target !== 'field') {
                return $submission->getFieldValue($fieldReference);
            }

            $resolved = Variables::getFieldAndValueForReference($fieldReference, $submission);
            $field = $resolved['field'] ?? null;

            if ($field) {
                $hasReferenceModifiers = $expression->default !== '' || $expression->transformerId !== '';

                if ($hasReferenceModifiers) {
                    // Once a reference opts into defaults/transformers, compare
                    // against the resolved expression output rather than the
                    // field's generic condition projection.
                    return $resolved['value'] ?? null;
                }

                $conditionValue = $submission->getFieldValueForCondition($field->handle);

                if ($expression->selector !== '') {
                    return ArrayHelper::getValue($conditionValue, str_replace(':', '.', $expression->selector));
                }

                return $conditionValue;
            }

            $field = $this->_findSubmissionField($submission, $expression->identifier);

            if ($field) {
                $conditionValue = $submission->getFieldValueForCondition($field->handle);

                if ($expression->selector !== '') {
                    return ArrayHelper::getValue($conditionValue, str_replace(':', '.', $expression->selector));
                }

                return $conditionValue;
            }

            return $resolved['value'] ?? null;
        }

        // Fallback for plain handles / dot notation.
        [$handle, $path] = array_pad(explode('.', $fieldReference, 2), 2, null);

        if (!$handle) {
            return null;
        }

        $field = $this->_findSubmissionField($submission, $handle);
        $conditionValue = $submission->getFieldValueForCondition($field?->handle ?? $handle);

        if ($path !== null && $path !== '') {
            return ArrayHelper::getValue($conditionValue, $path);
        }

        return $conditionValue;
    }

    private function _findSubmissionField(Submission $submission, string $identifier): mixed
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        foreach ($submission->getFields() as $field) {
            $matches = [
                (string)($field->handle ?? ''),
                (string)($field->uid ?? ''),
                (string)($field->reference ?? ''),
                $field->valueKey(),
            ];

            if (in_array($identifier, array_filter($matches), true)) {
                return $field;
            }
        }

        return null;
    }
}
