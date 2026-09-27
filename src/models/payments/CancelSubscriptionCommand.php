<?php
namespace verbb\formie\models\payments;

use verbb\formie\enums\PaymentCapabilityPurpose;
use verbb\formie\helpers\PaymentCapabilities;
use verbb\formie\models\Subscription;

use yii\web\ForbiddenHttpException;

final class CancelSubscriptionCommand
{
    // Public Methods
    // =========================================================================

    public function __construct(public readonly int $subscriptionId, public readonly string $token)
    {
    }

    public function authorize(Subscription $subscription): void
    {
        $capability = PaymentCapabilities::resolve($this->token, PaymentCapabilityPurpose::CANCEL);
        if ($this->subscriptionId !== $subscription->id || !$capability
            || (int)$capability['resourceId'] !== $subscription->id
            || ($capability['scope']['subscriptionUid'] ?? null) !== $subscription->uid
            || ($capability['scope']['integrationId'] ?? null) !== $subscription->integrationId
            || ($capability['scope']['submissionId'] ?? null) !== $subscription->submissionId) {
            throw new ForbiddenHttpException('Invalid cancellation capability.');
        }
    }
}
