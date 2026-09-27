<?php
namespace verbb\formie\compatibility\delivery;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\IntegrationTriggerEvents;
use verbb\formie\jobs\SendNotification;
use verbb\formie\models\IntegrationExecutionContext;

use RuntimeException;

/** Read old queued locators without restoring their mutable debugging payloads. */
trait LegacyDeliveryJobTrait
{
    // Properties
    // =========================================================================

    private array $_legacyDelivery = [];


    // Public Methods
    // =========================================================================

    public function __unserialize(array $data): void
    {
        if (isset($data['deliveryAttemptUid'])) {
            $this->deliveryAttemptUid = (string)$data['deliveryAttemptUid'];
            return;
        }

        $this->_legacyDelivery = array_intersect_key($data, array_flip(['submissionId', 'notificationId', 'integrationId', 'integrationHandle', 'stepHandles', 'executionUid', 'triggerEvent', 'operatorInitiated', 'runAfterNotifications']));
        $this->_legacyDelivery['executionUid'] ??= 'legacy:' . hash('sha256', serialize($this->_legacyDelivery));
    }

    public function __serialize(): array
    {
        return isset($this->deliveryAttemptUid) ? ['deliveryAttemptUid' => $this->deliveryAttemptUid] : $this->_legacyDelivery;
    }


    // Protected Methods
    // =========================================================================

    protected function resolveDeliveryAttemptUid(): string
    {
        if (isset($this->deliveryAttemptUid)) {
            return $this->deliveryAttemptUid;
        }

        $data = $this->_legacyDelivery;
        $submission = Submission::find()->id($data['submissionId'] ?? 0)->status(null)->isIncomplete(null)->isSpam(null)->one();
        if (!$submission) {
            throw new RuntimeException('Legacy delivery submission is unavailable.');
        }

        $notification = $this instanceof SendNotification ? Formie::$plugin->getNotifications()->getNotificationById($data['notificationId'] ?? 0) : null;
        if ($this instanceof SendNotification && !$notification) {
            throw new RuntimeException('Legacy notification is unavailable.');
        }

        if ($notification) {
            $binding = 'notification:' . ($notification->uid ?: $notification->id);
            $step = 'notification';
            $payload = ['notificationId' => $notification->id];
        } else {
            $binding = '@dispatch';
            $step = 'dispatch';
            $handles = $data['stepHandles'] ?? [];
            if (!$handles) {
                $handle = $data['integrationHandle'] ?? null;
                if (!$handle && !empty($data['integrationId'])) {
                    $handle = Formie::$plugin->getIntegrations()->getIntegrationById($data['integrationId'])?->handle;
                }
                $handles = $handle ? [$handle] : [];
            }
            if (!$handles) {
                throw new RuntimeException('Legacy integration is unavailable.');
            }
            $payload = ['handles' => $handles, 'triggerContext' => ['triggerEvent' => $data['triggerEvent'] ?? IntegrationTriggerEvents::SUBMIT, 'operatorInitiated' => (bool)($data['operatorInitiated'] ?? false)], 'afterNotifications' => (bool)($data['runAfterNotifications'] ?? false)];
        }

        $context = new IntegrationExecutionContext((int)$submission->id, (int)$submission->formId, $binding, $data['executionUid'], 'queued', 'legacy_job');
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $uid = $attempts->prepare($context, $step, $payload);
        $attempts->checkpoint($uid, 'legacy-job-imported', ['locators' => $data]);

        return $uid;
    }
}
