<?php
namespace verbb\formie\conditions;

use verbb\formie\elements\Submission;
use verbb\formie\helpers\References;
use verbb\formie\references\ReferenceContext;

class ConditionValueResolver
{
    // Public Methods
    // =========================================================================

    public function resolveFieldReferenceValue(mixed $fieldReference, Submission $submission): mixed
    {
        if (!is_string($fieldReference) || $fieldReference === '') {
            return $fieldReference;
        }
        // This is a declared reference operand. Stable plain handles are explicit compatibility selectors.
        $token = str_starts_with($fieldReference, '{') ? $fieldReference : References::field($fieldReference);
        $result = References::resolveValue($token, ReferenceContext::forSubmission($submission));
        $value = $result->requireValue();
        if ($result->field && $result->expression->selector === '' && $result->expression->transformerId === '' && !$result->expression->transformerParams) {
            return $result->field->getValueForCondition($value, $submission);
        }
        return $value;
    }
}
