<?php
namespace verbb\formie\enums;

enum PaymentResumeMode: string
{
    // Cases
    // =========================================================================

    case RESUBMIT = 'resubmit';
    case RETURN = 'return';
    case POLL = 'poll';
}
