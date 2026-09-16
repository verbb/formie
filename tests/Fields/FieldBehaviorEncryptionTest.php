<?php

declare(strict_types=1);

use craft\db\Query;
use verbb\formie\helpers\Table;

it('stores encrypted values as non-plaintext for multiple encrypted fields', function (): void {
    $secretA = 'Sensitive-A';
    $secretB = 'sensitive@example.test';

    $form = formie()
        ->form(['title' => 'Encryption Expanded'])
        ->singleLineTextField('secretOne', ['enableContentEncryption' => true])
        ->emailField('secretTwo', ['enableContentEncryption' => true])
        ->singleLineTextField('ordinary')
        ->create();

    $submission = formie()->submission($form)->with([
        'secretOne' => $secretA,
        'secretTwo' => $secretB,
        'ordinary' => 'Before',
    ])->save();

    $content = (new Query())
        ->select(['content'])
        ->from([Table::FORMIE_SUBMISSIONS])
        ->where(['id' => $submission->id])
        ->scalar();

    expect(is_string($content))->toBeTrue()
        ->and(str_contains((string)$content, $secretA))->toBeFalse()
        ->and(str_contains((string)$content, $secretB))->toBeFalse();

    // Reload twice: an unrelated update must not erase or double-encrypt stored secrets.
    $loaded = \verbb\formie\elements\Submission::find()->id($submission->id)->status(null)->one();
    expect($loaded->getFieldValue('secretOne'))->toBe($secretA)
        ->and($loaded->getFieldValue('secretTwo'))->toBe($secretB)
        ->and($loaded->getFieldValue('ordinary'))->toBe('Before');
    $loaded->setFieldValue('ordinary', 'After');
    expect(Craft::$app->getElements()->saveElement($loaded))->toBeTrue();
    $again = \verbb\formie\elements\Submission::find()->id($submission->id)->status(null)->one();
    expect($again->getFieldValue('secretOne'))->toBe($secretA)
        ->and($again->getFieldValue('secretTwo'))->toBe($secretB)
        ->and($again->getFieldValue('ordinary'))->toBe('After');
});
