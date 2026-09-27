<?php
namespace verbb\formie\enums;

enum PaymentCapabilityPurpose: string
{
    // Cases
    // =========================================================================

    case STATUS = 'payment.status';
    case RETURN = 'payment.return';
    case SESSION = 'payment.session';
    case CHALLENGE = 'payment.challenge';
    case CANCEL = 'payment.cancel';
}
