<?php

declare(strict_types=1);

use verbb\formie\services\Emails;

it('normalizes and splits recipient addresses before mail headers are built', function (): void {
    $emails = new Emails();
    $method = new ReflectionMethod(Emails::class, '_getParsedEmails');
    $method->setAccessible(true);

    $parsed = $method->invoke(
        $emails,
        "  FIRST@Example.TEST; second@example.test\r\nTHIRD@example.test  "
    );

    expect($parsed)->toBe([
        'first@example.test',
        'second@example.test',
        'third@example.test',
    ]);
})->group('security');
