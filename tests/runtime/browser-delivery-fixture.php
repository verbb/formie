<?php
require_once __DIR__ . '/verify.php';
$form = \verbb\formie\Formie::$plugin->getForms()->getFormByHandle('browserContract');
if (!$form) { throw new RuntimeException('Provision the owned browser fixture first.'); }
// A failed real Craft job exposes diagnostics through Craft's own queue detail UI.
$deliverySubmission = \verbb\formie\Formie::$plugin->getFactories()->submission($form)->with(['visitorName' => 'Delivery browser fixture', 'visitorEmail' => 'delivery@example.test'])->save();
$attempts = \verbb\formie\Formie::$plugin->getDeliveryAttempts();
$deliveryContext = new \verbb\formie\models\IntegrationExecutionContext($deliverySubmission->id, $form->id, '@dispatch', 'browser-delivery', 'queued');
$deliveryUid = $attempts->prepare($deliveryContext, 'dispatch', ['handles' => ['browserFixture'], 'triggerContext' => [], 'afterNotifications' => false]);
$attempts->checkpoint($deliveryUid, 'mapping-inputs', ['name' => ['kind' => 'exactReference', 'value' => 'field:visitorName'], 'password' => 'browser-never-display-secret']);
$attempts->checkpoint($deliveryUid, 'submission-projection', ['visitorName' => 'Delivery browser fixture']);
$attempts->checkpoint($deliveryUid, 'provider-error', ['status' => 504, 'message' => '<img src=x onerror="window.deliveryInjection=true"> Gateway response lost', 'apiKey' => 'browser-never-display-secret']);
$attempts->execute($deliveryUid, fn() => \verbb\formie\models\IntegrationResult::unknown('browser_simulated_response_loss'));
$deliveryJobId = Craft::$app->getQueue()->push(new \verbb\formie\jobs\TriggerIntegration(['deliveryAttemptUid' => $deliveryUid]));
file_put_contents(dirname(__DIR__, 2) . '/.cache/verbb-tests/delivery-browser.json', json_encode(['uid' => $deliveryUid, 'jobId' => $deliveryJobId, 'submissionUrl' => $deliverySubmission->getCpEditUrl(), 'submissionId' => $deliverySubmission->id]));
