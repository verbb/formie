<?php
namespace verbb\formie\enums;

enum SubmissionUploadStatus: string
{
    // Cases
    // =========================================================================

    case STAGED = 'staged';
    case BOUND = 'bound';
    case FINALIZED = 'finalized';
    case EXPIRED = 'expired';
    case REJECTED = 'rejected';
}
