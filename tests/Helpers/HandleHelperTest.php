<?php

declare(strict_types=1);

use verbb\formie\helpers\HandleHelper;

it('keeps unique handle suffixes within the configured maximum length', function (): void {
    $handle = str_repeat('a', 10);

    expect(HandleHelper::getUniqueHandle([], $handle, maxLength: 10))->toBe($handle)
        ->and(HandleHelper::getUniqueHandle([$handle], $handle, maxLength: 10))->toBe(str_repeat('a', 9) . '1')
        ->and(HandleHelper::getUniqueHandle([$handle, str_repeat('a', 9) . '1'], $handle, maxLength: 10))->toBe(str_repeat('a', 9) . '2')
        ->and(HandleHelper::getUniqueHandle([$handle, str_repeat('a', 9) . '1', str_repeat('a', 9) . '2'], $handle, maxLength: 10))->toHaveLength(10);
});

it('preserves the previous behavior when no maximum length is supplied', function (): void {
    expect(HandleHelper::getUniqueHandle(['example'], 'example'))->toBe('example1');
});
