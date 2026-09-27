<?php
namespace verbb\formie\models\payments;

final readonly class PaymentWebhookReceipt
{
    // Public Methods
    // =========================================================================

    public function __construct(
        public int $id,
        public int $integrationId,
        public string $integrationUid,
        public string $accountFingerprint,
        public string $environment,
        public string $providerEventId,
        public string $eventType,
        public ?string $resourceType,
        public ?string $resourceReference,
        public ?int $providerCreatedAt,
        public array $payload,
        public string $status,
        public int $attempts,
    ) {
    }
}
