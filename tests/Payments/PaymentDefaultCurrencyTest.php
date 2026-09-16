<?php

declare(strict_types=1);

use craft\i18n\Locale;
use verbb\formie\integrations\payments\Stripe;

it('uses the Craft locale for Stripe payment defaults', function (string $locale, string $currency): void {
    $previous = Craft::$app->getLocale();
    Craft::$app->set('locale', new Locale($locale));

    try {
        expect((new Stripe())->getPaymentFieldSettingsDefaults()['currencyFixed'])->toBe($currency);
    } finally {
        Craft::$app->set('locale', $previous);
    }
})->with([['en-AU', 'AUD'], ['en-US', 'USD'], ['fr-FR', 'EUR'], ['ja-JP', 'JPY']]);
