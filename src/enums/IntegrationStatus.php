<?php
namespace verbb\formie\enums;

enum IntegrationStatus: string
{
    // Cases
    // =========================================================================

    case Succeeded = 'succeeded';
    case Skipped = 'skipped';
    case Failed = 'failed';

    // Retired. Earlier releases stored these, and IntegrationResult reads both as failed.
    case Rejected = 'rejected';
    case Unknown = 'unknown';


    // Public Methods
    // =========================================================================

    /** @deprecated Every status is final. */
    public function isFinalized(): bool
    {
        return true;
    }
}
