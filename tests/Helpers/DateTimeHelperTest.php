<?php

declare(strict_types=1);

use verbb\formie\helpers\DateTimeHelper;

it('orders date subfield handles from date and time formats', function (): void {
    expect(DateTimeHelper::getSubfieldOrder([
        'includeDate' => true,
        'includeTime' => true,
        'dateFormat' => 'd/m/Y',
        'timeFormat' => 'H:i',
    ]))->toBe(['day', 'month', 'year', 'hour', 'minute']);
});

it('keeps unknown subfield configs after format-ordered handles', function (): void {
    $ordered = DateTimeHelper::orderSubfieldConfigs([
        'includeDate' => true,
        'includeTime' => false,
        'dateFormat' => 'Y-m-d',
    ], [
        ['handle' => 'day'],
        ['handle' => 'extra'],
        ['handle' => 'year'],
        ['handle' => 'month'],
    ]);

    expect(array_column($ordered, 'handle'))->toBe(['year', 'month', 'day', 'extra']);
});
