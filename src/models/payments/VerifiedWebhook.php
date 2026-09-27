<?php
namespace verbb\formie\models\payments;

use DateTimeImmutable;

final readonly class VerifiedWebhook
{
    // Public Methods
    // =========================================================================

    public function __construct(
        public string $providerEventId,
        public string $eventType,
        public ?string $resourceType,
        public ?string $resourceReference,
        public ?DateTimeImmutable $providerCreatedAt,
        public array $payload,
        public ?string $fingerprint = null,
    ) {
    }
}
