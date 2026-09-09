<?php

declare(strict_types=1);

use craft\helpers\Json;
use verbb\formie\migrations\m260709_000000_fix_double_encoded_submission_content;

it('detects double-encoded JSON content without MySQL-only SQL', function (): void {
    $migration = new m260709_000000_fix_double_encoded_submission_content();
    $method = new ReflectionMethod($migration, '_isDoubleEncodedContent');
    $method->setAccessible(true);

    $objectJson = Json::encode(['uid-1' => 'hello']);
    $doubleEncoded = Json::encode($objectJson);

    expect($method->invoke($migration, $objectJson))->toBeFalse()
        ->and($method->invoke($migration, $doubleEncoded))->toBeTrue()
        ->and($method->invoke($migration, ['already' => 'array']))->toBeFalse()
        ->and($method->invoke($migration, null))->toBeFalse();
});
