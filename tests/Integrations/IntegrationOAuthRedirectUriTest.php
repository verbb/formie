<?php

declare(strict_types=1);

use verbb\formie\base\Integration;
use verbb\formie\Formie;
use verbb\formie\integrations\emailmarketing\Mailchimp;

it('uses a Craft action URL for the default OAuth redirect URI', function (): void {
    $settings = Formie::$plugin->getSettings();
    $previous = $settings->redirectUri;

    $settings->redirectUri = null;

    try {
        $integration = new Mailchimp(['name' => 'Mailchimp', 'handle' => 'mailchimp']);

        expect($integration->getRedirectUri())
            ->toContain('actions/' . Integration::OAUTH_CALLBACK_ACTION);
    } finally {
        $settings->redirectUri = $previous;
    }
});

it('uses the plugin OAuth redirect URI override when configured', function (): void {
    $settings = Formie::$plugin->getSettings();
    $previous = $settings->redirectUri;

    $settings->redirectUri = 'https://oauth.example.test/formie/callback';

    try {
        $integration = new Mailchimp(['name' => 'Mailchimp', 'handle' => 'mailchimp']);

        expect($integration->getRedirectUri())->toBe('https://oauth.example.test/formie/callback');
    } finally {
        $settings->redirectUri = $previous;
    }
});
