<?php
namespace verbb\formie\compatibility\delivery;

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
        $metadata = (new DeliveryAttempt(
            (int)$submission->id,
            'integration:' . $integration->handle,
            $executionKey,
        ))->getMetadata();

        return ($metadata['state'] ?? '') === 'completed' ? IntegrationResult::succeeded() : null;
    }

    public static function notificationResponse(
        Submission $submission,
        Notification $notification,
        string $deliveryKey,
        string $attemptUid,
    ): ?array {
        $metadata = (new DeliveryAttempt(
            (int)$submission->id,
            'notification-send:' . ($notification->uid ?: $notification->id),
            $deliveryKey,
        ))->getMetadata();

        return ($metadata['state'] ?? '') === 'completed' ? ['success' => true] : null;
    }
}
