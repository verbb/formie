<?php

declare(strict_types=1);

use verbb\auth\clients\salesforce\token\SalesforceAccessToken;
use verbb\auth\models\Token;
use verbb\formie\integrations\crm\Salesforce;

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
