<?php

declare(strict_types=1);

use Composer\Semver\Semver;

it('only allows Stripe SDK releases that include the client used by the integration', function (): void {
    $package = json_decode(file_get_contents(dirname(__DIR__, 2) . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
    $constraint = $package['require']['stripe/stripe-php'];

    expect(Semver::satisfies('7.32.1', $constraint))->toBeFalse()
        ->and(Semver::satisfies('7.33.0', $constraint))->toBeTrue()
        ->and(Semver::satisfies('16.6.0', $constraint))->toBeTrue()
        ->and(class_exists(\Stripe\StripeClient::class))->toBeTrue();
});
