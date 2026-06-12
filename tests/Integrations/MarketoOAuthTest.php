<?php

declare(strict_types=1);

use verbb\formie\integrations\crm\Marketo;

it('normalizes Marketo API domain by removing a trailing /rest segment', function (): void {
    $integration = new Marketo([
        'name' => 'Marketo',
        'handle' => 'marketo',
        'apiDomain' => 'https://123-abc-456.mktorest.com/rest',
    ]);

    expect($integration->getApiDomain())->toBe('https://123-abc-456.mktorest.com');
});

it('does not send scope when requesting a Marketo access token', function (): void {
    $integration = new Marketo([
        'name' => 'Marketo',
        'handle' => 'marketo',
    ]);

    expect($integration->getAccessTokenOptions(['scope' => ['']]))
        ->toBe([]);
});
