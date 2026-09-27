<?php
namespace verbb\formie\models;

/** Request-local authorized upload claims, keyed by exact content path and asset. */
final class SubmissionUploadClaims
{
    // Properties
    // =========================================================================

    private array $_claims = [];


    // Public Methods
    // =========================================================================

    public function add(SubmissionUploadClaim $claim): void
    {
        $this->_claims[$claim->contentKey][$claim->assetId] = $claim;
    }

    public function get(string $contentKey, int $assetId): ?SubmissionUploadClaim
    {
        return $this->_claims[$contentKey][$assetId] ?? null;
    }
}
