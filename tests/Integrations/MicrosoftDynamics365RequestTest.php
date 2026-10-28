<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Token\AccessToken;
use verbb\auth\models\Token;
use verbb\formie\errors\IntegrationException;
use verbb\formie\errors\IntegrationStepException;
use verbb\formie\integrations\crm\MicrosoftDynamics365;

class DynamicsRequestFixture extends MicrosoftDynamics365
{
    public mixed $transport;

    public function getToken(): ?Token
    {
        $token = new Token();
        $token->setToken(new AccessToken(['access_token' => 'synthetic-dynamics-token', 'expires' => time() + 3600]));

        return $token;
    }

    protected function createDeliveryHttpHandler(): callable
    {
        return $this->transport;
    }
}

function dynamicsRequestFixture(array &$history, array $config = []): DynamicsRequestFixture
{
    // Keep the real OAuth request builder and origin guards, replacing only network I/O.
    $transport = HandlerStack::create(new MockHandler([
        new Response(200, ['Content-Type' => 'application/json'], '{"contactid":"contact-123"}'),
    ]));
    $transport->push(Middleware::history($history));
    $integration = new DynamicsRequestFixture(array_merge([
        'name' => 'Dynamics',
        'handle' => 'dynamics',
        'apiDomain' => 'https://8.8.8.8',
    ], $config));
    $integration->transport = $transport;

    return $integration;
}

it('sends Dynamics requests through its configured OAuth origin with the required headers', function(string $method, string $domain, string $version): void {
    $history = [];
    $integration = dynamicsRequestFixture($history, [
        'apiDomain' => $domain,
        'apiVersion' => $version,
        'impersonateUser' => true,
        'impersonateUserId' => 'synthetic-user-id',
    ]);
    $options = ['headers' => ['X-Custom-Header' => 'preserved']];

    if ($method !== 'GET') {
        $options['json'] = ['firstname' => 'Example'];
    }

    expect($integration->request($method, 'contacts?$select=contactid', $options))->toBe(['contactid' => 'contact-123'])
        ->and($history)->toHaveCount(1);

    $request = $history[0]['request'];
    expect((string)$request->getUri())->toBe("https://8.8.8.8/api/data/$version/contacts?\$select=contactid")
        ->and($request->getMethod())->toBe($method)
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer synthetic-dynamics-token')
        ->and($request->getHeaderLine('Accept'))->toBe('application/json')
        ->and($request->getHeaderLine('OData-MaxVersion'))->toBe('4.0')
        ->and($request->getHeaderLine('OData-Version'))->toBe('4.0')
        ->and($request->getHeaderLine('X-Custom-Header'))->toBe('preserved')
        ->and($request->getHeaderLine('Prefer'))->toBe($method === 'GET' ? '' : 'return=representation')
        ->and($request->getHeaderLine('CallerObjectId'))->toBe($method === 'POST' ? 'synthetic-user-id' : '')
        ->and($request->getHeaderLine('If-Match'))->toBe($method === 'PATCH' ? '*' : '')
        ->and($history[0]['options']['allow_redirects'])->toBeFalse();

    if ($method !== 'GET') {
        expect(json_decode((string)$request->getBody(), true))->toBe(['firstname' => 'Example']);
    }
})->with(['GET', 'POST', 'PATCH'])->with([
    ['https://8.8.8.8', 'v9.0'],
    ['https://8.8.8.8/', 'v9.2'],
]);

it('still rejects per-request Dynamics origin overrides before sending', function(): void {
    $history = [];
    $integration = dynamicsRequestFixture($history);

    expect(fn() => $integration->request('POST', 'contacts', ['base_uri' => 'https://1.1.1.1/']))
        ->toThrow(IntegrationException::class, 'A request cannot override the configured provider origin.')
        ->and($history)->toBeEmpty();
});

it('still rejects Dynamics targets outside the configured origin before sending', function(string $target): void {
    $history = [];
    $integration = dynamicsRequestFixture($history);

    expect(fn() => $integration->request('POST', $target))
        ->toThrow(IntegrationStepException::class)
        ->and($history)->toBeEmpty();
})->with(['https://1.1.1.1/contacts', 'http://8.8.8.8/contacts']);
