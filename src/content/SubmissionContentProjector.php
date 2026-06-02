<?php
namespace verbb\formie\content;

use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\models\ValueContext;

class SubmissionContentProjector
{
    // Public Methods
    // =========================================================================

    public function projectValueByContext(Submission $submission, FieldInterface $field, mixed $value, mixed $context): mixed
    {
        [$contextType, $contextData] = $this->_resolveContext($context);

        if ($contextType === null) {
            return $value;
        }

        return match ($contextType) {
            ValueContext::TYPE_STRING => $field->getValueAsString($value, $submission),
            ValueContext::TYPE_ARRAY, ValueContext::TYPE_JSON => $field->getValueAsArray($value, $submission),
            ValueContext::TYPE_EXPORT => $field->getValueForExport($value, $submission),
            ValueContext::TYPE_REFERENCE, ValueContext::TYPE_VARIABLE => $field->getValueForReference($value, $submission),
            ValueContext::TYPE_REFERENCE_BLOCK => isset($contextData['notification'])
                ? $field->getValueForReferenceBlock($value, $contextData['notification'], $submission)
                : $value,
            ValueContext::TYPE_SUMMARY => $field->getValueForSummary($value, $submission),
            ValueContext::TYPE_CONDITION => $field->getValueForCondition($value, $submission),
            // Incomplete email/integration context should degrade to the raw
            // normalized value instead of guessing, because guessing would hide
            // missing caller data and make the projection non-deterministic.
            ValueContext::TYPE_EMAIL => isset($contextData['notification'])
                ? $field->getValueForReferenceBlock($value, $contextData['notification'], $submission)
                : $value,
            ValueContext::TYPE_INTEGRATION => (isset($contextData['integrationField']) && isset($contextData['integration']))
                ? $field->getValueForIntegration($value, $contextData['integrationField'], $contextData['integration'], $submission, $contextData['fieldKey'] ?? '')
                : $value,
            default => $value,
        };
    }


    // Private Methods
    // =========================================================================

    private function _resolveContext(mixed $context): array
    {
        if ($context instanceof ValueContext) {
            return [$context->type, $context->params];
        }

        if (is_array($context)) {
            $contextType = $context['type'] ?? null;
            return [is_string($contextType) && $contextType !== '' ? $contextType : null, $context];
        }

        if (is_string($context) && $context !== '') {
            return [$context, []];
        }

        return [null, []];
    }
}
