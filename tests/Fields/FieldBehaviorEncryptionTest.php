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
        ->create();

    $submission = formie()->submission($form)->with([
        'secretOne' => $secretA,
        'secretTwo' => $secretB,
    ])->save();

    $content = (new Query())
        ->select(['content'])
        ->from([Table::FORMIE_SUBMISSIONS])
        ->where(['id' => $submission->id])
        ->scalar();

    expect(is_string($content))->toBeTrue()
        ->and(str_contains((string)$content, $secretA))->toBeFalse()
        ->and(str_contains((string)$content, $secretB))->toBeFalse();
});
