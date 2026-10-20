<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Tests\Support\WebRequestTestHelper;
use verbb\auth\Auth;
use verbb\formie\errors\IntegrationException;
use verbb\formie\integrations\crm\Salesforce;
use yii\log\Logger;

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

    $messageOffset = count(Craft::getLogger()->messages);
    $accessToken = WebRequestTestHelper::withWebRequestContext(fn() => $integration->getAccessToken());
    $token = Auth::getInstance()->getTokens()->createToken('formie', $integration, $accessToken);
    $integration->afterFetchAccessToken($token);

    expect($requests)->toHaveCount(1);
    $request = $requests[0]['request'];
    parse_str((string)$request->getBody(), $body);
    expect($request->getMethod())->toBe('POST')
        ->and((string)$request->getUri())->toBe('https://configured.my.salesforce.com/services/oauth2/token')
        ->and($request->getHeaderLine('Content-Type'))->toBe('application/x-www-form-urlencoded')
        ->and($body['grant_type'])->toBe('client_credentials')
        ->and($body['client_id'])->toBe('test-consumer-key')
        ->and($body['client_secret'])->toBe('test-consumer-secret')
        ->and($body)->not->toHaveKeys(['username', 'password', 'code', 'code_verifier'])
        ->and($integration->getBaseApiUrl($token))->toBe('https://returned.my.salesforce.com/services/data/v49.0/');

    $logs = array_values(array_filter(array_slice(Craft::getLogger()->messages, $messageOffset), fn(array $entry): bool => is_string($entry[0]) && str_starts_with($entry[0], 'Salesforce: Token request: ')));
    expect($logs)->toHaveCount(1)
        ->and($logs[0][1])->toBe(Logger::LEVEL_INFO)
        ->and($logs[0][2])->toStartWith('verbb\\formie\\');
    expect(json_decode(substr($logs[0][0], strlen('Salesforce: Token request: ')), true))->toBe([
        'endpoint' => (string)$request->getUri(),
        'grant_type' => $body['grant_type'],
    ]);
});

it('logs Salesforce token endpoints and grants through the normal logger when the exchange fails', function (string $grant, string $failure): void {
    $integration = new Salesforce([
        'name' => 'Salesforce',
        'grant' => $grant,
        'clientId' => 'private-consumer-key',
        'clientSecret' => 'private-consumer-secret',
        'authDomain' => 'https://configured.my.salesforce.com',
        'username' => 'private-username',
        'password' => 'private-password',
    ]);
    $provider = $integration->getOAuthProvider();
    $provider->setDomain('https://private-user:private-url-password@actual.my.salesforce.com:8443');
    $requests = [];
    $response = $failure === 'oauth'
        ? new Response(400, ['Content-Type' => 'application/json'], '{"error":"unsupported_grant_type","error_description":"grant type not supported"}')
        : new ConnectException('Transport failed', new Request('POST', 'https://actual.my.salesforce.com'));
    $handler = HandlerStack::create(new MockHandler([$response]));
    $handler->push(Middleware::history($requests));
    $provider->setHttpClient(new Client(['handler' => $handler]));
    $messageOffset = count(Craft::getLogger()->messages);

    expect(fn() => WebRequestTestHelper::withWebRequestContext(fn() => $integration->getAccessToken()))
        ->toThrow($failure === 'oauth' ? IdentityProviderException::class : ConnectException::class);

    $logs = array_values(array_filter(array_slice(Craft::getLogger()->messages, $messageOffset), fn(array $entry): bool => is_string($entry[0]) && str_starts_with($entry[0], 'Salesforce: Token request: ')));
    expect($logs)->toHaveCount(1);
    $diagnostics = json_decode(substr($logs[0][0], strlen('Salesforce: Token request: ')), true);
    expect($diagnostics)->toBe([
        'endpoint' => 'https://actual.my.salesforce.com:8443/services/oauth2/token',
        'grant_type' => $grant,
    ])->and($logs[0][0])->not->toContain('private-')
        ->and($requests)->toHaveCount(1);

    $request = $requests[0]['request'];
    parse_str((string)$request->getBody(), $body);
    expect($body['grant_type'])->toBe($grant)
        ->and($body['client_secret'])->toBe('private-consumer-secret')
        ->and((string)$request->getUri()->withUserInfo(''))->toBe($diagnostics['endpoint']);
})->with(['client_credentials', 'password'])->with(['oauth', 'transport']);
