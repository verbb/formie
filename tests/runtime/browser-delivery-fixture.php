<?php
require_once __DIR__ . '/verify.php';
$form = \verbb\formie\Formie::$plugin->getForms()->getFormByHandle('browserContract');
if (!$form) { throw new RuntimeException('Provision the owned browser fixture first.'); }
// Exercise every queue interface backed by durable delivery diagnostics, plus a
// regular Formie queue failure that must not expose the delivery-only action.
$deliverySubmission = \verbb\formie\Formie::$plugin->getFactories()->submission($form)->with(['visitorName' => 'Delivery browser fixture', 'visitorEmail' => 'delivery@example.test'])->save();
$attempts = \verbb\formie\Formie::$plugin->getDeliveryAttempts();

// Let Craft execute a genuine TriggerIntegration job whose provider fails at
// runtime. This guards the queue-to-child-attempt diagnostic path rather than
// only exercising a pre-resolved delivery attempt.
$runtimeExecutionUid = 'browser-trigger-integration-' . \craft\helpers\StringHelper::UUID();
$runtimeIntegration = static function($event) use ($form): void {
    if ((int)$event->form->id === (int)$form->id) {
        $event->integrations[] = new \Tests\Support\BrowserFailingIntegration([
            'name' => 'Browser failing integration',
            'handle' => 'browserRuntimeFailure',
            'enabled' => true,
        ]);
    }
};
\Tests\Support\BrowserFailingIntegration::$calls = 0;
\yii\base\Event::on(
    \verbb\formie\services\Integrations::class,
    \verbb\formie\services\Integrations::EVENT_MODIFY_FORM_INTEGRATIONS,
    $runtimeIntegration,
);

try {
    $previousQueueId = (int)((new \craft\db\Query())->from('{{%queue}}')->max('id') ?? 0);
    \verbb\formie\Formie::$plugin->getIntegrationRunner()->queueSteps(
        $deliverySubmission,
        ['browserRuntimeFailure'],
        \verbb\formie\enums\SubmissionOperation::SUBMIT,
        ['triggerEvent' => \verbb\formie\helpers\IntegrationTriggerEvents::SUBMIT],
        executionKey: $runtimeExecutionUid,
    );
    $runtimeUid = (string)(new \craft\db\Query())
        ->select('uid')
        ->from(\verbb\formie\services\DeliveryAttempts::TABLE)
        ->where(['submissionId' => $deliverySubmission->id, 'executionUid' => $runtimeExecutionUid, 'step' => 'dispatch'])
        ->scalar();
    $runtimeJobId = (int)(new \craft\db\Query())
        ->select('id')
        ->from('{{%queue}}')
        ->where(['>', 'id', $previousQueueId])
        ->orderBy(['id' => SORT_ASC])
        ->scalar();

    if (!$runtimeUid || !$runtimeJobId) {
        throw new RuntimeException('Unable to queue the TriggerIntegration browser fixture.');
    }

    $deliveryContext = new \verbb\formie\models\IntegrationExecutionContext($deliverySubmission->id, $form->id, '@dispatch', 'browser-delivery', 'queued');
    $deliveryUid = $attempts->prepare($deliveryContext, 'dispatch', ['handles' => ['browserFixture'], 'triggerContext' => [], 'afterNotifications' => false]);
    $attempts->checkpoint($deliveryUid, 'mapping-inputs', ['name' => ['kind' => 'exactReference', 'value' => 'field:visitorName'], 'password' => 'browser-never-display-secret']);
    $attempts->checkpoint($deliveryUid, 'submission-projection', ['visitorName' => 'Delivery browser fixture']);
    $attempts->checkpoint($deliveryUid, 'provider-error', ['status' => 504, 'message' => '<img src=x onerror="window.deliveryInjection=true"> Gateway response lost', 'apiKey' => 'browser-never-display-secret']);
    $attempts->execute($deliveryUid, fn() => \verbb\formie\models\IntegrationResult::unknown('browser_simulated_response_loss'));
    $deliveryJobId = Craft::$app->getQueue()->push(new \verbb\formie\jobs\TriggerIntegration(['deliveryAttemptUid' => $deliveryUid]));

    $notification = $form->getNotifications()[0] ?? null;
    if (!$notification || !$notification->id) { throw new RuntimeException('Provision the browser notification fixture first.'); }
    $notificationContext = new \verbb\formie\models\IntegrationExecutionContext($deliverySubmission->id, $form->id, 'notification:' . ($notification->uid ?: $notification->id), 'browser-notification', 'queued');
    $notificationUid = $attempts->prepare($notificationContext, 'notification', [
        'notificationId' => $notification->id,
        'acceptedFingerprint' => $attempts->operationFingerprint($deliverySubmission, $attempts->notificationConfiguration($notification)),
    ]);
    $attempts->checkpoint($notificationUid, 'submission-projection', ['visitorName' => 'Delivery browser fixture']);
    $attempts->checkpoint($notificationUid, 'email-response', ['success' => false, 'error' => 'Synthetic notification transport failure']);
    $attempts->execute($notificationUid, fn() => \verbb\formie\models\IntegrationResult::failed('email_failed'));
    $notificationJobId = Craft::$app->getQueue()->push(new \verbb\formie\jobs\SendNotification(['deliveryAttemptUid' => $notificationUid]));

    $nonDeliveryJobId = Craft::$app->getQueue()->push(new \verbb\formie\jobs\ImportForm());
    Craft::$app->getQueue()->run();

    $runtimeAttempt = $attempts->get($runtimeUid);
    $runtimeChild = (new \craft\db\Query())
        ->from(\verbb\formie\services\DeliveryAttempts::TABLE)
        ->where(['submissionId' => $deliverySubmission->id, 'executionUid' => $runtimeExecutionUid, 'step' => 'integration'])
        ->one();
    $runtimeResponseEvidence = $runtimeChild && (new \craft\db\Query())
        ->from(\verbb\formie\services\DeliveryAttempts::DIAGNOSTICS)
        ->where(['attemptId' => $runtimeChild['id'], 'checkpoint' => 'response-error'])
        ->exists();

    if (\Tests\Support\BrowserFailingIntegration::$calls !== 1 || ($runtimeAttempt['status'] ?? null) !== 'failed' || ($runtimeChild['status'] ?? null) !== 'failed' || !$runtimeResponseEvidence) {
        throw new RuntimeException('The TriggerIntegration browser fixture did not execute its provider failure.');
    }
} finally {
    \yii\base\Event::off(
        \verbb\formie\services\Integrations::class,
        \verbb\formie\services\Integrations::EVENT_MODIFY_FORM_INTEGRATIONS,
        $runtimeIntegration,
    );
}

file_put_contents(dirname(__DIR__, 2) . '/.cache/verbb-tests/delivery-browser.json', json_encode([
    'submissionUrl' => $deliverySubmission->getCpEditUrl(),
    'submissionId' => $deliverySubmission->id,
    'integration' => ['uid' => $deliveryUid, 'jobId' => $deliveryJobId],
    'triggerIntegration' => ['uid' => $runtimeUid, 'jobId' => $runtimeJobId],
    'notification' => ['uid' => $notificationUid, 'jobId' => $notificationJobId],
    'nonDelivery' => ['jobId' => $nonDeliveryJobId],
]));
