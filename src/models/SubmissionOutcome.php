<?php
namespace verbb\formie\models;

use verbb\formie\enums\SubmissionOutcomeType;

/**
 * An expected domain result. Adapters own protocol status and presentation.
 * Data contains only domain result fields, never request credentials or field values.
 */
final class SubmissionOutcome
{
    // Public Methods
    // =========================================================================

    public function __construct(
        public readonly SubmissionOutcomeType $type,
        public readonly ?int $submissionId = null,
        public readonly ?string $submissionUid = null,
        public readonly ?int $version = null,
        public readonly ?int $nextPageId = null,
        public readonly array $errors = [],
        public readonly array $data = [],
    ) {
    }
}
