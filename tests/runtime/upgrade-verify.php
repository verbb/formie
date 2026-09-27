<?php
require __DIR__ . '/upgrade-bootstrap.php';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';
use verbb\formie\Formie;
use verbb\formie\elements\{Form, Submission};

$fixture = json_decode(file_get_contents(dirname(__DIR__, 2) . '/.cache/verbb-tests/upgrade-fixture.json'), true, 512, JSON_THROW_ON_ERROR);
$check = static function (bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
    echo 'Verified: ' . $message . PHP_EOL;
};
$check(str_starts_with(realpath((new ReflectionClass(Formie::class))->getFileName()), realpath(dirname(__DIR__, 2) . '/src') . '/'), 'current checkout loaded after real Composer upgrade');
$check(!$app->getPlugins()->isPluginUpdatePending(Formie::$plugin), 'all Formie upgrade migrations applied');
$form = Form::find()->id($fixture['formId'])->status(null)->one();
$shared = Form::find()->id($fixture['sharedFormId'])->status(null)->one();
$submission = Submission::find()->id($fixture['submissionId'])->status(null)->one();
$check($form !== null && $shared !== null && $submission !== null, 'original form and submission identities survived');
$check($submission->stateVersion === 0, 'existing submissions receive an initial version without changing their content');
$check($app->getDb()->tableExists(\verbb\formie\helpers\Table::FORMIE_SUBMISSION_OPERATIONS), 'upgrade creates durable submission operation storage');
$check($app->getDb()->tableExists(\verbb\formie\helpers\Table::FORMIE_SUBMISSION_PROGRESS), 'upgrade creates canonical progress storage');
$check($app->getDb()->tableExists(\verbb\formie\helpers\Table::FORMIE_SUBMISSION_GRANTS), 'upgrade creates hash-only purpose-bound grant storage');
$check(!$app->getDb()->tableExists(\verbb\formie\helpers\Table::FORMIE_SUBMISSION_RESUME_TOKENS), 'upgrade removes plaintext beta resume storage');
$check($app->getDb()->columnExists(\verbb\formie\helpers\Table::FORMIE_PENDING_UPLOADS, 'contentHash'), 'upgrade creates recoverable upload promotion storage');
$check((string)$submission->getFieldValue('fullName') === 'Synthetic Ada', 'original text content survived');
$signatureAccess = (new \craft\db\Query())->select(['signatureAccessKey', 'legacySignatureAccess'])
    ->from(\verbb\formie\helpers\Table::FORMIE_SUBMISSIONS)->where(['id' => $submission->id])->one();
$legacySignature = \verbb\formie\helpers\SignatureAccess::resolveLegacyAccess($fixture['submissionUid'], (int)$fixture['signatureFieldId']);
$check($signatureAccess && $signatureAccess['signatureAccessKey'] === null && (bool)$signatureAccess['legacySignatureAccess'], 'Formie 3 submissions retain explicit legacy Signature access');
$check(($legacySignature['value'] ?? null) === $fixture['signatureValue'], 'unsigned Formie 3 Signature email URLs remain resolvable after upgrade');
$values = $submission->getValuesAsData();
$check(($values['company']['companyName'] ?? null) === 'Synthetic Company', 'nested group content survived');
$field = $form->getFieldByHandle('fullName');
$check($field->uid === $fixture['fieldUid'] && $field->required, 'field identity and required setting survived');
$check($field->fieldId === $shared->getFieldByHandle('fullName')->fieldId, 'legacy synced fields became one shared definition');
$notification = $form->getNotifications()[0] ?? null;
$check($notification !== null && $notification->name === 'Receipt' && !$notification->enabled, 'notification settings survived');
$check(str_contains((string)\verbb\formie\helpers\References::parseContent($notification->subject, $submission), 'Synthetic Ada'), 'migrated notification field reference resolves original content');
$check(\verbb\formie\helpers\References::parseContent($notification->getParsedContent(), $submission) === '<p>Saved Synthetic Ada</p>', 'notification body preserves its content and field value');
$check($app->getElements()->saveElement($submission, false) && $submission->stateVersion > 0, 'saving an upgraded submission advances its version');
foreach (['active' => 'active', 'cancelled' => 'cancelled', 'ambiguous' => 'unknown'] as $legacy => $expected) {
    $subscription = Formie::$plugin->getSubscriptions()->getSubscriptionById($fixture['subscriptionIds'][$legacy]);
    $check($subscription->status === $expected && !empty($subscription->history), 'legacy ' . $legacy . ' subscription has a coherent status and retained history');
}
foreach (['success' => 'success', 'pending' => 'unknown'] as $legacy => $expected) {
    $payment = Formie::$plugin->getPayments()->getPaymentById($fixture['paymentIds'][$legacy]);
    $check($payment->status === $expected && $payment->amount === '25.0100', 'exact legacy amount and ' . $legacy . ' payment certainty survived');
}
$app->getDb()->createCommand()->delete(\verbb\formie\helpers\Table::FORMIE_SUBSCRIPTIONS, ['id' => $fixture['subscriptionIds']['active']])->execute();
$payment = Formie::$plugin->getPayments()->getPaymentById($fixture['paymentIds']['success']);
$check($payment && $payment->subscriptionId === null && $payment->scope['subscriptionId'] === $fixture['subscriptionIds']['active'], 'upgraded payment history and owner snapshot survive subscription deletion');
$check($app->getDb()->tableExists(\verbb\formie\helpers\Table::FORMIE_WEBHOOK_RECEIPTS), 'encrypted receipt storage exists after populated upgrade');
echo "Populated Formie 3 → current Formie upgrade contract passed.\n";
$referenceContext = \verbb\formie\references\ReferenceContext::forSubmission($submission);
$check(\verbb\formie\helpers\References::resolveValue('{field:' . $field->reference . '}', $referenceContext)->requireValue() === 'Synthetic Ada', 'exact upgraded field instance resolves through the shared runtime');
$check(\verbb\formie\helpers\References::interpolateText('{field.fullName}', $referenceContext) === 'Synthetic Ada', 'stable Formie 3 dotted field syntax remains compatible');
$check(\verbb\formie\helpers\References::resolveValue('{env:SECURITY_KEY}', $referenceContext)->diagnostic === \verbb\formie\references\ReferenceDiagnostic::ForbiddenSource, 'upgrade does not implicitly expose environment secrets');

$check($app->getDb()->tableExists(\verbb\formie\services\DeliveryAttempts::TABLE) && $app->getDb()->tableExists(\verbb\formie\services\DeliveryAttempts::DIAGNOSTICS), 'upgrade creates durable delivery and diagnostic stores');
$rawDelivery = (new \craft\db\Query())->from(\verbb\formie\helpers\Table::FORMIE_INTEGRATIONS)->where(['handle' => 'upgradeDelivery'])->one();
$check($rawDelivery && !str_contains($rawDelivery['settings'], 'upgrade-literal-api-key'), 'legacy connection literals are encrypted at rest');
unset($rawDelivery['dateDeleted']);
$check(Formie::$plugin->getIntegrations()->createIntegration($rawDelivery)->apiKey === 'upgrade-literal-api-key', 'upgraded connection credentials hydrate correctly');
$rawFormSettings = (new \craft\db\Query())->select('settings')->from(\verbb\formie\helpers\Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar();
$check(!str_contains($rawFormSettings, 'upgrade-literal-password') && $form->settings->integrations['upgradeWebhook']['httpAuth']['password'] === 'upgrade-literal-password', 'legacy per-form secrets are encrypted and hydrate correctly');
$check(!str_contains(json_encode($app->getProjectConfig()->get('formie.integrations', true)), 'upgrade-literal-api-key'), 'project config no longer contains the legacy literal credential');
$check((new \craft\db\Query())->from(\verbb\formie\services\DeliveryAttempts::TABLE)->count() == 0, 'upgrade does not perform or invent external deliveries');

$check($app->getDb()->tableExists('{{%formie_instance_configs}}'), 'upgrade creates encrypted instance configuration storage');
$check($form->settings->completionBehavior === 'redirect' && $form->settings->completionRedirectSource === 'url' && $form->settings->submitActionUrl === '/upgraded-completion', 'legacy URL completion migrates to the explicit behavior and source');
$check($form->getFieldByHandle('fullName')->prefillQueryParam === 'legacyName', 'legacy query prefill name survives as prefillQueryParam');
$check($form->getFieldByHandle('legacyDate')->valueSource === 'dateInt', 'legacy Hidden defaultOption survives as valueSource');
$check(!str_contains((string)(new \craft\db\Query())->select('settings')->from(\verbb\formie\helpers\Table::FORMIE_FORM_FIELDS)->where(['id' => $form->getFieldByHandle('fullName')->id])->scalar(), 'prePopulate'), 'stored field instances use the canonical prefill key');

$companyConditions = $form->getFieldByHandle('company')->getConditions();
$check(($companyConditions['version'] ?? null) === 1 && ($companyConditions['conditions'][0]['legacyForward'] ?? false), 'stable Formie 3 field conditions receive schema version and legacy dependency policy');
$check(\verbb\formie\helpers\ConditionsHelper::evaluate($companyConditions, $submission)->value === true, 'upgraded field conditions evaluate original normalized content');
$check(($notification->conditions['version'] ?? null) === 1 && \verbb\formie\helpers\ConditionsHelper::evaluate($notification->conditions, $submission, 'notification')->value === true, 'upgraded notification conditions use the same canonical evaluator');
$submission->addError('field:company.companyName', '<b>Example error.</b>');
$check($submission->getSubmissionErrors()->toValuePathMap() === ['company.companyName' => ['Example error.']], 'legacy nested errors preserve complete value paths as safe text');
$check(isset($submission->getSubmissionErrors()->toClient()['fields'][$form->getFieldByHandle('company')->id . '.companyName']), 'upgraded nested client errors use the form-field instance identity');
