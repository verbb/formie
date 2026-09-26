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
