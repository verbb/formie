<?php
namespace verbb\formie\content;

use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Submission;

class SubmissionContentProjector
{
    // Public Methods
    // =========================================================================

    public function projectValue(Submission $submission, FieldInterface $field, mixed $value, FieldValueProjectionContext $context): mixed
    {
        return match ($context->projection) {
            FieldValueProjection::String => $field->getValueAsString($value, $submission),
            FieldValueProjection::Data => $field->getValueAsData($value, $submission),
            FieldValueProjection::Export => $field->getValueForExport($value, $submission),
            FieldValueProjection::Reference => $field->getValueForReference($value, $submission),
            FieldValueProjection::ReferenceBlock => $field->getValueForReferenceBlock($value, $context->notification, $submission),
            FieldValueProjection::Summary => $field->getValueForSummary($value, $submission),
            FieldValueProjection::Condition => $field->getValueForCondition($value, $submission),
            FieldValueProjection::Integration => $field->getValueForIntegration($value, $context->integrationField, $context->integration, $submission, $context->fieldKey),
        };
    }
}
