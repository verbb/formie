<?php
namespace verbb\formie\models\payments;

use InvalidArgumentException;

final readonly class VerifiedWebhookBatch
{
    // Public Methods
    // =========================================================================

    public function __construct(
        public string $accountFingerprint,
        public string $environment,
        public array $events,
        public array $evidenceHeaders = [],
    ) {
        if ($accountFingerprint === '' || $environment === '' || !$events) {
            throw new InvalidArgumentException('A verified webhook batch requires account identity, environment and events.');
        }

        foreach ($events as $event) {
            if (!$event instanceof VerifiedWebhook) {
                throw new InvalidArgumentException('Verified webhook batches may contain only verified events.');
            }
        }
    }
}
