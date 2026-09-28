<?php
namespace verbb\formie\enums;

enum SubscriptionStatus: string
{
    // Cases
    // =========================================================================

    case PENDING = 'pending';
    case TRIALING = 'trialing';
    case ACTIVE = 'active';
    case PAST_DUE = 'pastDue';
    case PAUSED = 'paused';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
    case COMPLETED = 'completed';
    case UNKNOWN = 'unknown';


    // Static Methods
    // =========================================================================

    public static function fromStored(string $status, ?string $providerStatus = null): self
    {
        $resolved = self::tryFrom($status);

        if ($resolved) {
            return $resolved;
        }

        return match ($status) {
            'suspended' => $providerStatus === 'paused' ? self::PAUSED : self::PAST_DUE,
            'cancelling' => self::ACTIVE,
            'expired' => $providerStatus === 'incomplete_expired' ? self::FAILED : self::COMPLETED,
            default => self::UNKNOWN,
        };
    }


    // Public Methods
    // =========================================================================

    public function isTerminal(): bool
    {
        return in_array($this, [self::CANCELLED, self::FAILED, self::COMPLETED], true);
    }

    public function isEstablished(): bool
    {
        return in_array($this, [self::TRIALING, self::ACTIVE, self::PAST_DUE, self::PAUSED], true);
    }
}
