<?php

declare(strict_types=1);

use craft\helpers\App;
use verbb\formie\base\Payment;
use verbb\formie\Formie;

it('uses the default dev payment proxy when dev mode is enabled', function (): void {
    expect(Payment::applyDevAccessibleUrl('https://formie.test/webhook', true, null))
        ->toBe('https://proxy.verbb.io?return=https://formie.test/webhook');
});

it('uses a custom dev payment proxy URL when configured', function (): void {
    expect(Payment::applyDevAccessibleUrl('https://formie.test/webhook', true, 'https://proxy.example.test/hook'))
        ->toBe('https://proxy.example.test/hook?return=https://formie.test/webhook');
});

it('disables the dev payment proxy when configured as an empty string', function (): void {
    expect(Payment::applyDevAccessibleUrl('https://formie.test/webhook', true, ''))
        ->toBe('https://formie.test/webhook');
});

it('does not wrap payment URLs when dev mode is disabled', function (): void {
    expect(Payment::applyDevAccessibleUrl('https://formie.test/webhook', false, 'https://proxy.example.test/hook'))
        ->toBe('https://formie.test/webhook');
});

it('wraps payment URLs through plugin settings in dev mode', function (): void {
    $settings = Formie::$plugin->getSettings();
    $previous = $settings->paymentWebhookProxyUrl;

    $settings->paymentWebhookProxyUrl = 'https://proxy.example.test/hook';

    try {
        if (!App::devMode()) {
            expect(Payment::applyPaymentWebhookProxy('https://formie.test/webhook'))
                ->toBe('https://formie.test/webhook');
        } else {
            expect(Payment::applyPaymentWebhookProxy('https://formie.test/webhook'))
                ->toBe('https://proxy.example.test/hook?return=https://formie.test/webhook');
        }
    } finally {
        $settings->paymentWebhookProxyUrl = $previous;
    }
});
