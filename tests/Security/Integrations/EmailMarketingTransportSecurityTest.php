<?php

declare(strict_types=1);

use verbb\formie\integrations\emailmarketing\Ecomail;
use verbb\formie\integrations\emailmarketing\Moosend;

it('uses tls without changing email marketing provider authentication', function (string $integrationClass, string $baseUri, string $configKey, string $authKey): void {
    $apiKey = 'synthetic-provider-key';
    $integration = new $integrationClass([
        'apiKey' => $apiKey,
    ]);
    $client = $integration->getClient();

    expect((string)$client->getConfig('base_uri'))->toBe($baseUri)
        ->and($client->getConfig($configKey)[$authKey] ?? null)->toBe($apiKey);
})->with([
    'Ecomail header authentication' => [Ecomail::class, 'https://api2.ecomailapp.com/', 'headers', 'key'],
    'Moosend query authentication' => [Moosend::class, 'https://api.moosend.com/v3/', 'query', 'apikey'],
])->group('security');
