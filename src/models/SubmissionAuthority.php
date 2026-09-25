<?php
namespace verbb\formie\models;

use verbb\formie\enums\SubmissionAuthorityType;

/**
 * A boundary-established authority binding. Transport payloads cannot hydrate it.
 * The processor verifies the credential or permission before constructing it.
 */
final class SubmissionAuthority
{
    // Public Methods
    // =========================================================================

    public function __construct(
        public readonly SubmissionAuthorityType $type,
        public readonly int $formId,
        public readonly ?int $submissionId,
        public readonly string $scope,
    ) {
    }
}
