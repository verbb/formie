<?php
namespace verbb\formie\models\payments;

use verbb\formie\enums\SubscriptionCancellationMode;
use verbb\formie\enums\SubscriptionStatus;

use DateTimeImmutable;

/** Immutable provider observation; persistence and transition policy belong to Subscriptions. */
final readonly class SubscriptionSnapshot
{
    // Public Methods
    // =========================================================================

    public function __construct(
        public SubscriptionStatus $status,
        public string $providerStatus,
        public ?string $reference = null,
        public ?int $providerUpdatedAt = null,
        public ?string $providerEventId = null,
        public ?DateTimeImmutable $startedAt = null,
        public ?DateTimeImmutable $trialStartsAt = null,
        public ?DateTimeImmutable $trialEndsAt = null,
        public ?DateTimeImmutable $currentPeriodStartsAt = null,
        public ?DateTimeImmutable $currentPeriodEndsAt = null,
        public ?DateTimeImmutable $nextPaymentAt = null,
        public ?DateTimeImmutable $pausedAt = null,
        public ?DateTimeImmutable $cancelAt = null,
        public ?DateTimeImmutable $cancelledAt = null,
        public ?DateTimeImmutable $endedAt = null,
        public ?SubscriptionCancellationMode $cancellationMode = null,
        public array $rawData = [],
    ) {
    }
}
