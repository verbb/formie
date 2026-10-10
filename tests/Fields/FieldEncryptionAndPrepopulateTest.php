<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Json;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\console\controllers\EncryptionController;
use verbb\formie\content\ContentEncryption;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\Table;

it('stores encrypted field values as non-plaintext payloads when encryption is enabled', function (): void {
    $plainText = 'HighlySensitiveValue-123';

    $form = formie()
        ->form(['title' => 'Encryption Contract'])
        ->singleLineTextField('secretValue', ['enableContentEncryption' => true])
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['secretValue' => $plainText])
        ->save();

    $content = (new Query())
        ->select(['content'])
        ->from([Table::FORMIE_SUBMISSIONS])
        ->where(['id' => $submission->id])
        ->scalar();

    expect(is_string($content))->toBeTrue()
        ->and(str_contains((string)$content, $plainText))->toBeFalse()
        ->and(Json::decode((string)$content)[$form->getFieldByHandle('secretValue')->uid]['__formie_enc'])->toBe(2);

    $loaded = \verbb\formie\elements\Submission::find()->id($submission->id)->status(null)->one();
    expect($loaded->getFieldValue('secretValue'))->toBe($plainText);
});

it('locks unavailable encrypted values without changing their stored bytes', function (): void {
    $form = formie()
        ->form(['title' => 'Unavailable Encryption Key Contract'])
        ->singleLineTextField('secretValue', ['enableContentEncryption' => true])
        ->singleLineTextField('visibleValue')
        ->create();

    $submission = formie()->submission($form)->with(['secretValue' => 'initial', 'visibleValue' => 'still readable'])->save();
    $field = $form->getFieldByHandle('secretValue');
    $oldKey = ContentEncryption::generateKey();
    $content = Json::decode((string)(new Query())
        ->select(['content'])
        ->from([Table::FORMIE_SUBMISSIONS])
        ->where(['id' => $submission->id])
        ->scalar());
    $content[$field->uid] = [
        '__formie_enc' => 2,
        'k' => $oldKey->id,
        'c' => ContentEncryption::encryptV2(Json::encode('rescuable secret'), $oldKey),
    ];
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSIONS, [
        'content' => $content,
    ], ['id' => $submission->id])->execute();

    $storedBefore = (new Query())
        ->select(['content'])
        ->from([Table::FORMIE_SUBMISSIONS])
        ->where(['id' => $submission->id])
        ->scalar();

    $locked = Submission::find()->id($submission->id)->status(null)->one();
    expect($locked->hasLockedContent())->toBeTrue()
        ->and($locked->getFieldValueForExport('secretValue'))->toBe('[encrypted: unavailable]')
        ->and($locked->getFieldValueForExport('visibleValue'))->toBe('still readable')
        ->and(fn() => Craft::$app->getElements()->duplicateElement($locked))->toThrow(\craft\errors\InvalidElementException::class);
    $locked->title = 'Must not save';
    expect(Craft::$app->getElements()->saveElement($locked, false))->toBeFalse();
    expect((new Query())
        ->select(['content'])
        ->from([Table::FORMIE_SUBMISSIONS])
        ->where(['id' => $submission->id])
        ->scalar())->toBe($storedBefore);

    $settings = Formie::$plugin->getSettings();
    $previousKeys = $settings->encryptionPreviousKeys;
    $settings->encryptionPreviousKeys = [$oldKey->value];

    try {
        $rescued = Submission::find()->id($submission->id)->status(null)->one();
        expect($rescued->hasLockedContent())->toBeFalse()
            ->and($rescued->getFieldValue('secretValue'))->toBe('rescuable secret');
    } finally {
        $settings->encryptionPreviousKeys = $previousKeys;
    }

    Craft::$app->getElements()->deleteElement($rescued, true);
});

it('distinguishes absent, malformed and valid content-encryption keys', function (): void {
    $legacyRoot = ContentEncryption::generateKey();
    $settings = Formie::$plugin->getSettings();
    $previousKeys = $settings->encryptionPreviousKeys;
    $settings->encryptionPreviousKeys = [$legacyRoot->value];
    $legacyEnvelope = [
        '__formie_encrypted' => 1,
        'ciphertext' => base64_encode(Craft::$app->getSecurity()->encryptByKey(Json::encode('legacy root-shaped key'), $legacyRoot->value)),
    ];

    try {
        expect(ContentEncryption::parseKey(ContentEncryption::generateKey()->value))->toBeInstanceOf(\verbb\formie\content\ContentEncryptionKey::class)
            ->and(fn() => ContentEncryption::parseKey('not-a-formie-key'))->toThrow(RuntimeException::class)
            ->and(fn() => ContentEncryption::parseKey('formie-root:v1:short'))->toThrow(RuntimeException::class)
            ->and(\verbb\formie\content\FieldStorageCodec::decode($legacyEnvelope))->toBe('legacy root-shaped key');
    } finally {
        $settings->encryptionPreviousKeys = $previousKeys;
    }
});

it('blocks newly encrypted fields without a key while grandfathering existing fields', function (): void {
    $form = formie()
        ->form(['title' => 'Encryption Toggle Contract'])
        ->singleLineTextField('existingSecret', ['enableContentEncryption' => true])
        ->create();
    $existing = $form->getFieldByHandle('existingSecret');
    $configuredKey = getenv('FORMIE_ENCRYPTION_KEY');
    putenv('FORMIE_ENCRYPTION_KEY');
    unset($_ENV['FORMIE_ENCRYPTION_KEY'], $_SERVER['FORMIE_ENCRYPTION_KEY']);

    try {
        $new = new \verbb\formie\fields\SingleLineText([
            'handle' => 'newSecret',
            'label' => 'New Secret',
            'enableContentEncryption' => true,
        ]);
        expect($new->validate(['enableContentEncryption']))->toBeFalse()
            ->and($new->getErrors('enableContentEncryption'))->not->toBeEmpty()
            ->and($existing->validate(['enableContentEncryption']))->toBeTrue();
    } finally {
        putenv('FORMIE_ENCRYPTION_KEY=' . $configuredKey);
        $_ENV['FORMIE_ENCRYPTION_KEY'] = $_SERVER['FORMIE_ENCRYPTION_KEY'] = $configuredKey;
    }
});

it('deliberately re-encrypts readable legacy values under the active key', function (): void {
    $form = formie()
        ->form(['title' => 'Re-encryption Command Contract'])
        ->singleLineTextField('secretValue', ['enableContentEncryption' => true])
        ->create();
    $submission = formie()->submission($form)->with(['secretValue' => 'legacy secret'])->save();
    $field = $form->getFieldByHandle('secretValue');
    $content = Json::decode((string)(new Query())
        ->select(['content'])
        ->from([Table::FORMIE_SUBMISSIONS])
        ->where(['id' => $submission->id])
        ->scalar());
    $content[$field->uid] = [
        '__formie_encrypted' => 1,
        'ciphertext' => base64_encode(Craft::$app->getSecurity()->encryptByKey(
            Json::encode('legacy secret'),
            Craft::$app->getConfig()->getGeneral()->securityKey,
        )),
    ];
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSIONS, [
        'content' => $content,
    ], ['id' => $submission->id])->execute();

    $plaintextSubmission = formie()->submission($form)->with(['secretValue' => 'historical plaintext'])->save();
    $plaintextContent = Json::decode((string)(new Query())
        ->select(['content'])
        ->from([Table::FORMIE_SUBMISSIONS])
        ->where(['id' => $plaintextSubmission->id])
        ->scalar());
    $plaintextContent[$field->uid] = 'historical plaintext';
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSIONS, [
        'content' => $plaintextContent,
    ], ['id' => $plaintextSubmission->id])->execute();

    $controller = new EncryptionController('encryption', Formie::$plugin);
    $controller->interactive = false;
    $controller->confirmBackup = true;
    $controller->keyId = ContentEncryption::currentKey()->id;
    $controller->batchSize = 10;

    expect($controller->actionReencrypt())->toBe(\yii\console\ExitCode::OK);
    $stored = Json::decode((string)(new Query())
        ->select(['content'])
        ->from([Table::FORMIE_SUBMISSIONS])
        ->where(['id' => $submission->id])
        ->scalar());
    expect($stored[$field->uid]['__formie_enc'])->toBe(2)
        ->and($stored[$field->uid]['k'])->toBe(ContentEncryption::currentKey()->id)
        ->and(Submission::find()->id($submission->id)->status(null)->one()->getFieldValue('secretValue'))->toBe('legacy secret');
    $plaintextStored = Json::decode((string)(new Query())
        ->select(['content'])
        ->from([Table::FORMIE_SUBMISSIONS])
        ->where(['id' => $plaintextSubmission->id])
        ->scalar());
    expect($plaintextStored[$field->uid]['__formie_enc'])->toBe(2)
        ->and(Submission::find()->id($plaintextSubmission->id)->status(null)->one()->getFieldValue('secretValue'))->toBe('historical plaintext');
});

it('accepts only authenticated browser relation payloads while preserving empty forms', function (): void {
    $form = formie()->form(['title' => 'Relation Token Contract'])->create();
    $relations = [['id' => 12, 'siteId' => 1, 'type' => \craft\elements\Entry::class]];
    $ciphertext = Craft::$app->getSecurity()->encryptByKey(
        Json::encode($relations),
        Craft::$app->getConfig()->getGeneral()->securityKey,
    );
    $token = 'base64:' . base64_encode('crypt:' . $ciphertext);

    WebRequestTestHelper::withWebRequestContext(function () use ($form, $relations): void {
        expect($form->getRelationsFromRequest())->toBe($relations);
    }, ['method' => 'POST', 'bodyParams' => ['relations' => $token]]);

    WebRequestTestHelper::withWebRequestContext(function () use ($form): void {
        expect($form->getRelationsFromRequest())->toBeNull();
    }, ['method' => 'POST']);

    WebRequestTestHelper::withWebRequestContext(function () use ($form, $relations): void {
        expect(fn() => $form->getRelationsFromRequest())->toThrow(\yii\web\BadRequestHttpException::class);
    }, ['method' => 'POST', 'bodyParams' => ['relations' => Json::encode($relations)]]);
});

it('stores encryption-marker-shaped plaintext without interpreting it as ciphertext', function (): void {
    $validCiphertext = \verbb\formie\helpers\StringHelper::encenc('private');
    $malformedCiphertext = 'base64:' . base64_encode('crypt:broken');

    $form = formie()
        ->form(['title' => 'Plaintext Marker Contract'])
        ->singleLineTextField('validMarker')
        ->singleLineTextField('malformedMarker')
        ->create();

    $submission = formie()->submission($form)->with([
        'validMarker' => $validCiphertext,
        'malformedMarker' => $malformedCiphertext,
    ])->save();

    $content = (new Query())
        ->select(['content'])
        ->from([Table::FORMIE_SUBMISSIONS])
        ->where(['id' => $submission->id])
        ->scalar();

    expect($content)->toContain('formie:plain:v1:8d986bd501b14ae6a8de160e74b602db');

    $loaded = \verbb\formie\elements\Submission::find()->id($submission->id)->status(null)->one();
    expect($loaded->getFieldValue('validMarker'))->toBe($validCiphertext)
        ->and($loaded->getFieldValue('malformedMarker'))->toBe($malformedCiphertext);
});

it('keeps pre-populate query-string feature callable in current runtime context', function (): void {
    $form = formie()
        ->form(['title' => 'PrePopulate Contract'])
        ->singleLineTextField('fullName', [
            'defaultValue' => 'Default Name',
            'prePopulate' => 'fullName',
        ])
        ->create();

    $field = $form->getFieldByHandle('fullName');

    WebRequestTestHelper::withWebRequestContext(function () use ($field, $form): void {
        expect($field?->getDefaultValue())->toBe('Default Name')
            ->and($field?->getInitialValue($form))->toBe('Peter Sherman');
    }, [
        'queryParams' => [
            'fullName' => 'Peter Sherman',
        ],
    ]);
});

it('keeps template populate values separate from field defaults', function (): void {
    $form = formie()
        ->form(['title' => 'Populate Contract'])
        ->singleLineTextField('fullName', [
            'defaultValue' => 'Default Name',
        ])
        ->create();

    Formie::$plugin->getRendering()->populateFormValues($form, [
        'fullName' => 'Template Prefill',
    ]);

    $field = $form->getFieldByHandle('fullName');

    expect($field?->getDefaultValue())->toBe('Default Name')
        ->and($field?->getInitialValue($form))->toBe('Template Prefill')
        ->and($field?->getElementValue($form))->toBe('Template Prefill');
});

it('does not execute Twig in hidden field initial values', function (): void {
    $form = formie()
        ->form(['title' => 'Hidden Initial Value Contract'])
        ->hiddenField('trackingToken', [
            'defaultOption' => 'custom',
            'defaultValue' => '{{ 7 * 7 }}',
        ])
        ->create();

    $field = $form->getFieldByHandle('trackingToken');
    $inputOptions = $field?->getInputTemplateVariables($form, $field?->getElementValue($form));

    expect($inputOptions)->toBeArray()
        ->and($inputOptions['value'] ?? null)->toBe('{{ 7 * 7 }}');
});
