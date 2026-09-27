<?php
require __DIR__ . '/upgrade-bootstrap.php';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';
use verbb\formie\Formie;
use verbb\formie\elements\{Form, Submission};
use verbb\formie\models\{FieldLayout, Notification};
use verbb\formie\fields\{Group, Signature, SingleLineText};

if (Formie::$plugin->version !== '3.1.39') { throw new RuntimeException('The fixture must start on the pinned Formie 3 release.'); }
$form = new Form(['title' => 'Upgrade contract', 'handle' => 'upgradeContract']);
$form->setFormLayout(new FieldLayout(['pages' => [['label' => 'Details', 'rows' => [['fields' => [
    ['type' => SingleLineText::class, 'label' => 'Full name', 'handle' => 'fullName', 'required' => true, 'prePopulate' => 'legacyName'],
    ['type' => \verbb\formie\fields\Hidden::class, 'label' => 'Legacy date', 'handle' => 'legacyDate', 'defaultOption' => 'dateInt'],
    ['type' => \verbb\formie\fields\Payment::class, 'label' => 'Payment', 'handle' => 'payment'],
    ['type' => Signature::class, 'label' => 'Signature', 'handle' => 'signature'],
    ['type' => Group::class, 'label' => 'Company', 'handle' => 'company', 'enableConditions' => true, 'conditions' => ['conditionRule' => 'all', 'showRule' => 'show', 'conditions' => [['field' => 'fullName', 'condition' => '=', 'value' => 'Synthetic Ada']]], 'rows' => [['fields' => [
        ['type' => SingleLineText::class, 'label' => 'Company name', 'handle' => 'companyName'],
    ]]]],
]]]]]]));
$form->settings->submitAction = 'url';
$form->settings->submitActionUrl = '/upgraded-completion';
$form->settings->integrations = ['upgradeWebhook' => ['enabled' => true, 'httpAuth' => ['password' => 'upgrade-literal-password']]];
$form->setNotifications([new Notification(['name' => 'Receipt', 'handle' => 'receipt', 'enabled' => false, 'enableConditions' => true, 'conditions' => ['conditionRule' => 'all', 'showRule' => 'show', 'conditions' => [['field' => 'fullName', 'condition' => '=', 'value' => 'Synthetic Ada']]], 'subject' => 'Hello {field:fullName}',
    'to' => 'fixture@example.test', 'content' => '<p>Saved {field:fullName}</p>'])]);
if (!$app->getElements()->saveElement($form)) { throw new RuntimeException(json_encode($form->getErrors())); }
$shared = new Form(['title' => 'Shared upgrade contract', 'handle' => 'sharedUpgradeContract']);
$shared->setFormLayout(new FieldLayout(['pages' => [['label' => 'Details', 'rows' => [['fields' => [
    ['type' => SingleLineText::class, 'label' => 'Full name', 'handle' => 'fullName', 'required' => true, 'prePopulate' => 'legacyName', 'syncId' => $form->getFieldByHandle('fullName')->id],
]]]]]]));
if (!$app->getElements()->saveElement($shared)) { throw new RuntimeException(json_encode($shared->getErrors())); }
$persistedField = Form::find()->id($form->id)->status(null)->one()->getFieldByHandle('fullName');
if ($persistedField->prePopulate !== 'legacyName') { throw new RuntimeException('Legacy shared-field seed lost its query prefill before upgrade.'); }
$submission = new Submission();
$submission->setForm($form);
$signatureValue = 'data:image/png;base64,' . base64_encode('Legacy Formie 3 signature');
$submission->setFieldValues(['fullName' => 'Synthetic Ada', 'signature' => $signatureValue, 'company' => ['companyName' => 'Synthetic Company']]);
if (!$app->getElements()->saveElement($submission, false)) { throw new RuntimeException(json_encode($submission->getErrors())); }
$saved = Submission::find()->id($submission->id)->status(null)->one();
if ((string)$saved->getFieldValue('fullName') !== 'Synthetic Ada') { throw new RuntimeException('Legacy fixture did not persist its control value.'); }
if (\verbb\formie\helpers\Variables::getParsedValue('Hello {field:fullName}', $saved) !== 'Hello Synthetic Ada') {
    throw new RuntimeException('Legacy notification reference does not resolve before migration.');
}
$integration = new \verbb\formie\integrations\payments\Stripe(['name' => 'Upgrade finance', 'handle' => 'upgradeFinance']);
Formie::$plugin->getIntegrations()->saveIntegration($integration, false);
$deliveryIntegration = new \verbb\formie\integrations\helpdesk\Freshdesk(['name' => 'Upgrade delivery', 'handle' => 'upgradeDelivery', 'apiDomain' => 'https://example.test', 'apiKey' => 'upgrade-literal-api-key']);
if (!Formie::$plugin->getIntegrations()->saveIntegration($deliveryIntegration, false)) { throw new RuntimeException('Unable to seed legacy connection.'); }
$subscriptionIds = [];
foreach (['active', 'cancelled', 'ambiguous'] as $state) {
    $subscription = new \verbb\formie\models\Subscription(['integrationId' => $integration->id, 'submissionId' => $submission->id,
        'reference' => 'sub_upgrade_' . $state, 'trialDays' => 0, 'hasStarted' => true,
        'isCanceled' => $state !== 'active', 'isSuspended' => $state === 'ambiguous']);
    if (!Formie::$plugin->getSubscriptions()->saveSubscription($subscription)) { throw new RuntimeException('Unable to seed financial history.'); }
    $subscriptionIds[$state] = $subscription->id;
}
$paymentIds = [];
foreach (['success', 'pending'] as $status) {
    $payment = new \verbb\formie\models\Payment(['integrationId' => $integration->id, 'submissionId' => $submission->id,
        'fieldId' => $form->getFieldByHandle('payment')->id, 'subscriptionId' => $subscriptionIds['active'], 'reference' => 'pi_upgrade_' . $status, 'amount' => 25.01, 'currency' => 'USD', 'status' => $status]);
    if (!Formie::$plugin->getPayments()->savePayment($payment)) { throw new RuntimeException('Unable to seed payment.'); }
    $paymentIds[$status] = $payment->id;
}
file_put_contents(dirname(__DIR__, 2) . '/.cache/verbb-tests/upgrade-fixture.json', json_encode([
    'subscriptionIds' => $subscriptionIds, 'paymentIds' => $paymentIds, 'from' => Formie::$plugin->version, 'formId' => $form->id, 'sharedFormId' => $shared->id, 'submissionId' => $submission->id,
    'fieldUid' => $form->getFieldByHandle('fullName')->uid, 'signatureFieldId' => $form->getFieldByHandle('signature')->id,
    'submissionUid' => $submission->uid, 'signatureValue' => $signatureValue,
], JSON_PRETTY_PRINT));
echo "Persisted Formie 3 forms, shared field, nested content and notification.\n";
