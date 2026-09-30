<?php
namespace verbb\formie\enums;

enum PaymentDecisionStatus: string
{
    // Cases
    // =========================================================================

    case NOT_REQUIRED = 'notRequired';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case ACTION_REQUIRED = 'requiresAction';
    case PENDING = 'pending';
    case CANCELLED = 'cancelled';
    case UNKNOWN = 'unknown';
}
