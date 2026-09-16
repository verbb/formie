<?php
require __DIR__ . '/upgrade-bootstrap.php';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';
use verbb\formie\Formie;
use verbb\formie\elements\{Form, Submission};
use verbb\formie\models\{FieldLayout, Notification};
use verbb\formie\fields\{SingleLineText, Group};

if (Formie::$plugin->version !== '3.1.39') { throw new RuntimeException('The fixture must start on the pinned Formie 3 release.'); }
$form = new Form(['title' => 'Upgrade contract', 'handle' => 'upgradeContract']);
$form->setFormLayout(new FieldLayout(['pages' => [['label' => 'Details', 'rows' => [['fields' => [
    ['type' => SingleLineText::class, 'label' => 'Full name', 'handle' => 'fullName', 'required' => true],
    ['type' => Group::class, 'label' => 'Company', 'handle' => 'company', 'rows' => [['fields' => [
        ['type' => SingleLineText::class, 'label' => 'Company name', 'handle' => 'companyName'],
    ]]]],
]]]]]]));
$form->setNotifications([new Notification(['name' => 'Receipt', 'handle' => 'receipt', 'enabled' => false, 'subject' => 'Hello {field:fullName}',
    'to' => 'fixture@example.test', 'content' => '<p>Saved {field:fullName}</p>'])]);
if (!$app->getElements()->saveElement($form)) { throw new RuntimeException(json_encode($form->getErrors())); }
$shared = new Form(['title' => 'Shared upgrade contract', 'handle' => 'sharedUpgradeContract']);
$shared->setFormLayout(new FieldLayout(['pages' => [['label' => 'Details', 'rows' => [['fields' => [
    ['type' => SingleLineText::class, 'label' => 'Full name', 'handle' => 'fullName', 'required' => true, 'syncId' => $form->getFieldByHandle('fullName')->id],
]]]]]]));
if (!$app->getElements()->saveElement($shared)) { throw new RuntimeException(json_encode($shared->getErrors())); }
$submission = new Submission();
$submission->setForm($form);
$submission->setFieldValues(['fullName' => 'Synthetic Ada', 'company' => ['companyName' => 'Synthetic Company']]);
if (!$app->getElements()->saveElement($submission, false)) { throw new RuntimeException(json_encode($submission->getErrors())); }
$saved = Submission::find()->id($submission->id)->status(null)->one();
if ((string)$saved->getFieldValue('fullName') !== 'Synthetic Ada') { throw new RuntimeException('Legacy fixture did not persist its control value.'); }
if (\verbb\formie\helpers\Variables::getParsedValue('Hello {field:fullName}', $saved) !== 'Hello Synthetic Ada') {
    throw new RuntimeException('Legacy notification reference does not resolve before migration.');
}
file_put_contents(dirname(__DIR__, 2) . '/.cache/verbb-tests/upgrade-fixture.json', json_encode([
    'from' => Formie::$plugin->version, 'formId' => $form->id, 'sharedFormId' => $shared->id, 'submissionId' => $submission->id,
    'fieldUid' => $form->getFieldByHandle('fullName')->uid,
], JSON_PRETTY_PRINT));
echo "Persisted Formie 3 forms, shared field, nested content and notification.\n";
