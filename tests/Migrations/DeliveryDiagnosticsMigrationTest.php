<?php

use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use verbb\formie\Formie;
use verbb\formie\migrations\m260929_000000_encrypt_delivery_diagnostics;
use verbb\formie\models\IntegrationExecutionContext;

it('encrypts retained beta diagnostic checkpoints without losing their support evidence', function (): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->save();
    $attempts = Formie::$plugin->getDeliveryAttempts();
    $uid = $attempts->prepare(new IntegrationExecutionContext($submission->id, $form->id, 'migration', 'diagnostic-migration'), 'integration');
    $attempt = $attempts->get($uid);
    Craft::$app->getDb()->createCommand()->insert($attempts::DIAGNOSTICS, [
        'attemptId' => $attempt['id'],
        'checkpoint' => 'legacy-plaintext',
        'data' => Json::encode(['person' => 'Legacy Diagnostic Person', 'apiKey' => '[redacted]']),
        'dateCreated' => Db::prepareDateForDb(new DateTime()),
    ])->execute();

    $migration = new m260929_000000_encrypt_delivery_diagnostics();
    expect($migration->safeUp())->toBeTrue()
        ->and($migration->safeUp())->toBeTrue();

    $stored = (string)(new Query())
        ->select('data')
        ->from($attempts::DIAGNOSTICS)
        ->where(['attemptId' => $attempt['id'], 'checkpoint' => 'legacy-plaintext'])
        ->scalar();

    expect($stored)->not->toContain('Legacy Diagnostic Person')
        ->and(Json::encode($attempts->supportBundle($uid)))->toContain('Legacy Diagnostic Person');
});
