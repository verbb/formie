<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;
use verbb\formie\compatibility\delivery\LegacyDeliveryJobTrait;
use verbb\formie\enums\IntegrationStatus;

use Craft;
use craft\queue\BaseJob;

use RuntimeException;

class TriggerIntegration extends BaseJob implements DeliveryJobInterface
{
    // Traits
    // =========================================================================

    use DebuggableJobTrait;
    use LegacyDeliveryJobTrait;


    // Properties
    // =========================================================================

    public string $deliveryAttemptUid;


    // Public Methods
    // =========================================================================

    public function getDeliveryAttemptUid(): string
    {
        return $this->resolveDeliveryAttemptUid();
    }

    public function execute($queue): void
    {
        $uid = $this->getDeliveryAttemptUid();
        $result = Formie::$plugin->getIntegrationRunner()->runQueuedAttempt($uid);
        if (!in_array($result->status, [IntegrationStatus::Succeeded, IntegrationStatus::Skipped], true)) {
            throw new RuntimeException('Integration delivery ' . $result->status->value . '. Open Formie delivery diagnostics.');
        }
        $this->setProgress($queue, 1);
    }


    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): string
    {
        return Craft::t('formie', 'Delivering form integrations.');
    }
}
