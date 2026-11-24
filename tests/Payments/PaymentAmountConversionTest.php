<?php

use verbb\formie\integrations\payments\Opayo;
use verbb\formie\integrations\payments\Stripe;

it('converts decimal payment amounts without adding a cent', function (float $amount, int $minor): void {
    expect(Stripe::toStripeAmount($amount, 'USD'))->toBe($minor)
        ->and(Opayo::toOpayoAmount($amount, 'GBP'))->toBe($minor);
    expect(Stripe::toStripeAmount(Stripe::fromStripeAmount($minor, 'USD'), 'USD'))->toBe($minor)
        ->and(Opayo::toOpayoAmount(Opayo::fromOpayoAmount($minor, 'GBP'), 'GBP'))->toBe($minor);
})->with([
    [0.07, 7], [1.10, 110], [9.95, 995], [19.99, 1999], [25.01, 2501], [100.01, 10001],
]);

it('uses Stripe API units for zero-decimal and legacy two-decimal currencies', function (string $currency, int $minor): void {
    expect(Stripe::toStripeAmount(500, $currency))->toBe($minor)
        ->and(Stripe::fromStripeAmount($minor, $currency))->toBe('500');
})->with(['JPY' => ['JPY', 500], 'ISK' => ['ISK', 50000], 'UGX' => ['UGX', 50000]]);

it('sends MGA to Stripe as a zero-decimal currency', function (string $amount, int $stripe, string $back): void {
    expect(Stripe::toStripeAmount($amount, 'MGA'))->toBe($stripe)
        ->and(Stripe::fromStripeAmount($stripe, 'MGA'))->toBe($back);
})->with([['1000', 1000, '1000.00'], ['1000.00', 1000, '1000.00'], ['5000', 5000, '5000.00']]);

it('rejects fractions of MGA, which Stripe cannot charge', function (): void {
    expect(fn() => Stripe::toStripeAmount('1000.50', 'MGA'))->toThrow(InvalidArgumentException::class);
});

it('sends three-decimal currencies to Stripe in thousandths rounded to the nearest ten', function (string $currency): void {
    expect(Stripe::toStripeAmount('1.5', $currency))->toBe(1500)
        ->and(Stripe::toStripeAmount('1.230', $currency))->toBe(1230)
        ->and(Stripe::toStripeAmount('1.234', $currency))->toBe(1230)
        ->and(Stripe::toStripeAmount('1.235', $currency))->toBe(1240)
        ->and(Stripe::toStripeAmount('9.999', $currency))->toBe(10000)
        ->and(Stripe::fromStripeAmount(1230, $currency))->toBe('1.230');
})->with(['BHD', 'JOD', 'KWD', 'OMR', 'TND']);
