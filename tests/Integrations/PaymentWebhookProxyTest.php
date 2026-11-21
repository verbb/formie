<?php

declare(strict_types=1);

use craft\helpers\App;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use verbb\formie\base\Payment;
use verbb\formie\Formie;
use verbb\formie\integrations\payments\{GoCardless, Mollie, Stripe};

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

// Providers send webhooks as signed POSTs. The proxy can only redirect, which drops the body, so webhook URLs must
// always point straight at the site, even in dev mode.
it('never sends payment webhook URLs through the dev proxy', function (): void {
    expect(App::devMode())->toBeTrue();

    foreach ([new Stripe(), new GoCardless(), new Mollie()] as $integration) {
        $integration->uid = StringHelper::UUID();

        expect($integration->getWebhookUrl())
            ->toBe(UrlHelper::siteUrl('formie/payment-webhooks/process-webhook', ['integrationUid' => $integration->uid]));
    }
});

// The visitor's browser follows a redirect, so return URLs can still use the proxy.
it('keeps the dev proxy for Mollie and GoCardless return URLs', function (): void {
    expect(App::devMode())->toBeTrue();

    foreach ([new GoCardless(), new Mollie()] as $integration) {
        expect($integration->getReturnUrl(['returnToken' => 'abc']))->toStartWith('https://proxy.verbb.io?return=');
    }
});
