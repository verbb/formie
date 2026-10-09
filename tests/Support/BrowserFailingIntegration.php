<?php

declare(strict_types=1);

namespace Tests\Support;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationResult;

final class BrowserFailingIntegration extends Integration
{
    public static int $calls = 0;

    public static function displayName(): string
    {
        return 'Browser failing integration';
    }

    public function fetchConfig(): IntegrationConfig
    {
        return new IntegrationConfig();
    }

    public function sendPayload(Submission $submission): IntegrationResult
    {
        self::$calls++;
        $this->beginPayloadDelivery($submission);

        if ($attemptUid = $this->getDeliveryAttemptUid()) {
            Formie::$plugin->getDeliveryAttempts()->checkpoint($attemptUid, 'response-error', [
                'status' => 422,
                'message' => 'The browser fixture provider rejected the payload.',
                'response' => ['error' => 'invalid_fixture_payload'],
            ]);
        }

        return IntegrationResult::failed('browser_provider_rejected', true);
    }
}
