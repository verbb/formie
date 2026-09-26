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
$values = $submission->getValuesAsArray();
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
