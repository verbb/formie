<?php
namespace verbb\formie\models\payments;

use verbb\formie\base\Payment as PaymentIntegration;
use verbb\formie\enums\PaymentCapabilityPurpose;
use verbb\formie\enums\SubscriptionCancellationMode;
use verbb\formie\helpers\PaymentCapabilities;
use verbb\formie\models\Subscription;

use yii\web\ForbiddenHttpException;

final class CancelSubscriptionCommand
{
    // Public Methods
    // =========================================================================

    public function __construct(
        public readonly int $subscriptionId,
        public readonly string $token,
        public readonly ?SubscriptionCancellationMode $mode = null,
    ) {
    }

    public function authorize(Subscription $subscription): void
    {
        $capability = PaymentCapabilities::resolve($this->token, PaymentCapabilityPurpose::CANCEL);
        $mode = $this->resolveMode($subscription);

        if ($this->subscriptionId !== $subscription->id || !$capability
            || (int)$capability['resourceId'] !== $subscription->id
            || ($capability['scope']['subscriptionUid'] ?? null) !== $subscription->uid
            || ($capability['scope']['integrationId'] ?? null) !== $subscription->integrationId
            || ($capability['scope']['submissionId'] ?? null) !== $subscription->submissionId
            || ($capability['scope']['cancellationMode'] ?? null) !== $mode->value) {
            throw new ForbiddenHttpException('Invalid cancellation capability.');
        }
    }

    public function resolveMode(Subscription $subscription): SubscriptionCancellationMode
    {
        if ($this->mode) {
            return $this->mode;
        }

        $integration = $subscription->getIntegration();

        return $integration instanceof PaymentIntegration
            ? $integration->getDefaultSubscriptionCancellationMode()
            : SubscriptionCancellationMode::IMMEDIATE;
    }
}
