<?php

declare(strict_types=1);

use verbb\auth\clients\salesforce\token\SalesforceAccessToken;
use verbb\auth\models\Token;
use verbb\formie\integrations\crm\Salesforce;

it('resolves the configured Salesforce OAuth grant', function (array $config, string $expected): void {
    $integration = new Salesforce(array_merge([
        'name' => 'Salesforce',
        'handle' => 'salesforce',
    ], $config));

    expect($integration->getGrant())->toBe($expected);
})->with([
    'authorization code by default' => [[], Salesforce::GRANT_AUTHORIZATION_CODE],
    'client credentials' => [['grant' => Salesforce::GRANT_CLIENT_CREDENTIALS], Salesforce::GRANT_CLIENT_CREDENTIALS],
    'password' => [['grant' => Salesforce::GRANT_PASSWORD], Salesforce::GRANT_PASSWORD],
    'legacy use credentials' => [['useCredentials' => true], Salesforce::GRANT_PASSWORD],
    'explicit grant overrides legacy setting' => [['grant' => Salesforce::GRANT_AUTHORIZATION_CODE, 'useCredentials' => true], Salesforce::GRANT_AUTHORIZATION_CODE],
]);

it('uses a configured Salesforce authentication domain before production or sandbox defaults', function (): void {
    $integration = new Salesforce([
        'name' => 'Salesforce',
        'handle' => 'salesforce',
        'authDomain' => 'https://example.my.salesforce.com/',
        'useSandbox' => true,
    ]);

    expect($integration->getAuthDomain())->toBe('https://example.my.salesforce.com')
        ->and($integration->getApiDomain())->toBe('https://example.my.salesforce.com')
        ->and($integration->getOAuthProviderConfig()['domain'])->toBe('https://example.my.salesforce.com');
});

it('uses Salesforce production and sandbox authentication domains by default', function (): void {
    $production = new Salesforce([
        'name' => 'Salesforce',
        'handle' => 'salesforce-production',
    ]);
    $sandbox = new Salesforce([
        'name' => 'Salesforce',
        'handle' => 'salesforce-sandbox',
        'useSandbox' => true,
    ]);

    expect($production->getApiDomain())->toBe('https://login.salesforce.com')
        ->and($sandbox->getApiDomain())->toBe('https://test.salesforce.com');
});

it('resolves Salesforce instance URL from token values', function (): void {
    $integration = new Salesforce([
        'name' => 'Salesforce',
        'handle' => 'salesforce',
    ]);

    $token = new Token([
        'values' => [
            'instance_url' => 'https://example.my.salesforce.com/',
        ],
    ]);

    expect($integration->getInstanceUrl($token))->toBe('https://example.my.salesforce.com')
        ->and($integration->getBaseApiUrl($token))->toBe('https://example.my.salesforce.com/services/data/v49.0/');
});

it('falls back to integration apiDomain when token values are missing instance_url', function (): void {
    $integration = new Salesforce([
        'name' => 'Salesforce',
        'handle' => 'salesforce',
        'apiDomain' => 'https://legacy.my.salesforce.com',
    ]);

    $token = new Token([
        'values' => [],
    ]);

    expect($integration->getInstanceUrl($token))->toBe('https://legacy.my.salesforce.com')
        ->and($integration->getBaseApiUrl($token))->toBe('https://legacy.my.salesforce.com/services/data/v49.0/');
});

it('reads instance URL from SalesforceAccessToken when values are empty', function (): void {
    $integration = new Salesforce([
        'name' => 'Salesforce',
        'handle' => 'salesforce',
    ]);

    $accessToken = new SalesforceAccessToken([
        'access_token' => 'test-token',
        'instance_url' => 'https://token.my.salesforce.com',
    ]);

    $token = new Token([
        'values' => [],
    ]);
    $token->setToken($accessToken);

    expect($integration->getInstanceUrl($token))->toBe('https://token.my.salesforce.com');
});

it('stores instance_url on the token during afterFetchAccessToken', function (): void {
    $integration = new Salesforce([
        'name' => 'Salesforce',
        'handle' => 'salesforce',
        'apiDomain' => 'https://stored.my.salesforce.com',
    ]);

    $token = new Token([
        'values' => [],
    ]);

    $integration->afterFetchAccessToken($token);

    expect($token->values['instance_url'])->toBe('https://stored.my.salesforce.com')
        ->and($integration->apiDomain)->toBe('https://stored.my.salesforce.com');
});
