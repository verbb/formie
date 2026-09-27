<?php
namespace verbb\formie\compatibility\delivery;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\DeliveryAttempt;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\Notification;

final class LegacyDeliveryAttempts
{
    // Static Methods
    // =========================================================================

    public static function integrationResult(
        Submission $submission,
        Integration $integration,
        string $executionKey,
        string $attemptUid,
    ): ?IntegrationResult {
        $attempts = Formie::$plugin->getDeliveryAttempts();
        if ($attempts->hasReconciliation($attemptUid)) {
            return null;
        }

        $metadata = (new DeliveryAttempt(
            (int)$submission->id,
            'integration:' . $integration->handle,
            $executionKey,
        ))->getMetadata();

        if (!in_array($metadata['state'] ?? '', ['completed', 'sending', 'unknown'], true)) {
            return null;
        }

        return $metadata['state'] === 'completed'
            ? IntegrationResult::succeeded()
            : IntegrationResult::unknown('legacy_delivery_unresolved');
    }

    public static function notificationResponse(
        Submission $submission,
        Notification $notification,
        string $deliveryKey,
        string $attemptUid,
    ): ?array {
        $attempts = Formie::$plugin->getDeliveryAttempts();
        if ($attempts->hasReconciliation($attemptUid)) {
            return null;
        }

        $metadata = (new DeliveryAttempt(
            (int)$submission->id,
            'notification-send:' . ($notification->uid ?: $notification->id),
            $deliveryKey,
        ))->getMetadata();

        if (!in_array($metadata['state'] ?? '', ['completed', 'sending', 'unknown'], true)) {
            return null;
        }

        return [
            'success' => $metadata['state'] === 'completed',
            'deliveryOutcomeUnknown' => $metadata['state'] !== 'completed',
        ];
    }
}
