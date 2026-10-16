<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Tests\Support\WebRequestTestHelper;
use verbb\auth\Auth;
use verbb\formie\errors\IntegrationException;
use verbb\formie\integrations\crm\Salesforce;

it('uses a saved Salesforce instance domain for existing client credentials connections', function (): void {
    $integration = new Salesforce([
        'grant' => Salesforce::GRANT_CLIENT_CREDENTIALS,
        'apiDomain' => 'https://existing.my.salesforce.com/',
    ]);

    expect($integration->getApiDomain())->toBe('https://existing.my.salesforce.com');
});

it('rejects generic or missing Salesforce client credentials endpoints before connecting', function (?string $domain): void {
    $integration = new Salesforce([
        'grant' => Salesforce::GRANT_CLIENT_CREDENTIALS,
        'authDomain' => $domain,
    ]);

    expect(fn() => $integration->getOAuthProvider())->toThrow(IntegrationException::class, 'My Domain');
})->with([null, '', 'https://login.salesforce.com', 'https://test.salesforce.com/']);

it('exchanges Salesforce client credentials at My Domain and retains the returned API instance', function (): void {
    $integration = new Salesforce([
        'name' => 'Salesforce',
        'handle' => 'salesforce',
        'grant' => Salesforce::GRANT_CLIENT_CREDENTIALS,
        'clientId' => 'test-consumer-key',
        'clientSecret' => 'test-consumer-secret',
        'authDomain' => 'https://configured.my.salesforce.com/',
        'apiDomain' => 'https://old.my.salesforce.com',
    ]);
    $requests = [];
    $handler = HandlerStack::create(new MockHandler([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'access_token' => 'test-access-token',
            'token_type' => 'Bearer',
            'instance_url' => 'https://returned.my.salesforce.com',
        ])),
    ]));
    $handler->push(Middleware::history($requests));
    $integration->getOAuthProvider()->setHttpClient(new Client(['handler' => $handler]));

    $accessToken = WebRequestTestHelper::withWebRequestContext(fn() => $integration->getAccessToken());
    $token = Auth::getInstance()->getTokens()->createToken('formie', $integration, $accessToken);
    $integration->afterFetchAccessToken($token);

    expect($requests)->toHaveCount(1);
    $request = $requests[0]['request'];
    parse_str((string)$request->getBody(), $body);
    expect($request->getMethod())->toBe('POST')
        ->and((string)$request->getUri())->toBe('https://configured.my.salesforce.com/services/oauth2/token')
        ->and($body['grant_type'])->toBe('client_credentials')
        ->and($body['client_id'])->toBe('test-consumer-key')
        ->and($body['client_secret'])->toBe('test-consumer-secret')
        ->and($body)->not->toHaveKeys(['username', 'password', 'code', 'code_verifier'])
        ->and($integration->getBaseApiUrl($token))->toBe('https://returned.my.salesforce.com/services/data/v49.0/');
});
