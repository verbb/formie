<?php

declare(strict_types=1);

use verbb\formie\gql\types\FormIntegrationsType;
use verbb\formie\integrations\automations\Make;
use verbb\formie\integrations\automations\N8n;
use verbb\formie\integrations\automations\WebRequest;
use verbb\formie\integrations\captchas\Turnstile;

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;

it('keeps automation destination URLs out of public GraphQL settings', function (): void {
    $integrations = [
        new Make(['webhook' => 'https://hook.example.test/make-secret']),
        new N8n(['webhook' => 'https://hook.example.test/n8n-secret']),
        new WebRequest(['url' => 'https://api.example.test/receive?token=web-request-secret']),
    ];

    foreach ($integrations as $integration) {
        expect(graphqlIntegrationSettings($integration))->toBeNull();
    }
})->group('security');

it('preserves public captcha settings in GraphQL', function (): void {
    $settings = graphqlIntegrationSettings(new Turnstile([
        'siteKey' => 'browser-public-site-key',
        'secretKey' => 'server-private-secret-key',
        'theme' => 'dark',
    ]));

    expect(json_decode($settings, true))
        ->toMatchArray([
            'siteKey' => 'browser-public-site-key',
            'theme' => 'dark',
        ])
        ->not->toHaveKey('secretKey');
})->group('security');

it('removes sensitive attributes from extension GraphQL settings', function (): void {
    $integration = new class(['webhook' => 'https://hook.example.test/extension-secret']) extends Make {
        public function allowedGqlSettings(): array
        {
            return [
                'endpoint' => $this->webhook,
                'displayMode' => 'compact',
                'nested' => [
                    'endpoint' => $this->webhook,
                    'displayMode' => 'expanded',
                ],
            ];
        }
    };

    expect(json_decode(graphqlIntegrationSettings($integration), true))->toBe([
        'displayMode' => 'compact',
        'nested' => [
            'displayMode' => 'expanded',
        ],
    ]);
})->group('security');

function graphqlIntegrationSettings(object $integration): ?string
{
    $schema = new Schema([
        'query' => new ObjectType([
            'name' => 'IntegrationSettingsSecurityQuery',
            'fields' => [
                'integration' => [
                    'type' => FormIntegrationsType::getType(),
                    'resolve' => fn() => $integration,
                ],
            ],
        ]),
    ]);
    $result = GraphQL::executeQuery($schema, '{ integration { settings } }')->toArray();

    expect($result)->not->toHaveKey('errors');

    return $result['data']['integration']['settings'];
}
