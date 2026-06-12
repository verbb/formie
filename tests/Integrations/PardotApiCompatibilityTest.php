<?php

declare(strict_types=1);

use verbb\formie\integrations\crm\Pardot;

it('detects Pardot Classic accounts that cannot use API v4', function (): void {
    $method = new ReflectionMethod(Pardot::class, '_getApiCompatibilityError');
    $method->setAccessible(true);

    $message = $method->invoke(null, [
        '@attributes' => [
            'stat' => 'fail',
            'version' => 1,
            'err_code' => 89,
        ],
        'err' => 'Your account is unable to use version 4 of the API.',
    ]);

    expect($message)->toBeString()
        ->and($message)->toContain('API v4')
        ->and($message)->toContain('Pardot Classic');
});

it('returns null for unrelated Pardot API responses', function (): void {
    $method = new ReflectionMethod(Pardot::class, '_getApiCompatibilityError');
    $method->setAccessible(true);

    expect($method->invoke(null, ['prospect' => ['id' => 123]]))->toBeNull();
});
