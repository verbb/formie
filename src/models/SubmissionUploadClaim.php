<?php
namespace verbb\formie\models;

/**
 * Immutable proof that one staged upload was authorized for an exact submission value path.
 * The plaintext capability is deliberately discarded before the workflow receives this claim.
 */
final class SubmissionUploadClaim
{
    // Public Methods
    // =========================================================================

    public function __construct(
        public readonly int $uploadId,
        public readonly string $uploadUid,
        public readonly int $assetId,
        public readonly int $formId,
        public readonly int $siteId,
        public readonly string $fieldUid,
        public readonly string $contentKey,
        public readonly string $browserHash,
        public readonly ?int $submissionId,
        public readonly ?int $progressId,
    ) {
    }
}
