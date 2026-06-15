<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\helpers\Table;

it('has abuse control columns in the spam settings table', function (): void {
    $schema = Craft::$app->getDb()->getSchema()->getTableSchema(Table::FORMIE_SPAM_SETTINGS, true);

    expect($schema?->getColumn('enableFormSubmitExpiration'))->not->toBeNull()
        ->and($schema?->getColumn('enableSuspiciousTextDetection'))->not->toBeNull();
});

it('reads abuse control values from spam protection defaults', function (): void {
    $defaults = Formie::$plugin->getSpamProtection()->getDefaultValues();

    expect($defaults)->toHaveKey('enableSuspiciousTextDetection')
        ->and($defaults['enableMaximumLinks'])->toBeFalse();
});
