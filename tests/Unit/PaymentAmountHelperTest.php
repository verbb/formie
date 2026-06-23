<?php

declare(strict_types=1);

use verbb\formie\helpers\PaymentAmountHelper;

it('parses plain numeric payment amounts', function (): void {
    expect(PaymentAmountHelper::parseAmount(750))->toBe(750.0)
        ->and(PaymentAmountHelper::parseAmount('750.00'))->toBe(750.0)
        ->and(PaymentAmountHelper::parseAmount('£750.00'))->toBe(750.0);
});

it('parses US-style thousands separators', function (): void {
    expect(PaymentAmountHelper::parseAmount('1,234.56'))->toBe(1234.56)
        ->and(PaymentAmountHelper::parseAmount('1,750'))->toBe(1750.0);
});

it('parses EU-style thousands separators', function (): void {
    expect(PaymentAmountHelper::parseAmount('1.234,56'))->toBe(1234.56)
        ->and(PaymentAmountHelper::parseAmount('12,50'))->toBe(12.5);
});
