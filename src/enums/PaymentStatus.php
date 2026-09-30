<?php
namespace verbb\formie\enums;

enum PaymentStatus: string
{
    // Cases
    // =========================================================================

    case PENDING = 'pending';
    case REQUIRES_ACTION = 'requiresAction';
    case PROCESSING = 'processing';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case UNKNOWN = 'unknown';
    case CANCELLED = 'cancelled';
}
