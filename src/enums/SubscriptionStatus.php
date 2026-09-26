<?php
namespace verbb\formie\enums;

enum SubscriptionStatus: string
{
    // Cases
    // =========================================================================

    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case CANCELLING = 'cancelling';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
    case UNKNOWN = 'unknown';


    // Public Methods
    // =========================================================================

    public function isTerminal(): bool
    {
        return $this === self::CANCELLED || $this === self::EXPIRED;
    }
}
