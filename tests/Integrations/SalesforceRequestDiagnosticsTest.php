<?php

declare(strict_types=1);

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Token\AccessToken;
use verbb\auth\models\Token;
use verbb\formie\integrations\crm\Salesforce;

class SalesforceDiagnosticTransportFixture extends Salesforce
{
    public mixed $transport;

    public function getToken(): ?Token
    {
        $token = new Token();
        $token->setToken(new AccessToken(['access_token' => 'private-stored-token', 'expires' => time() + 3600]));
        $token->values = ['instance_url' => 'https://8.8.8.8'];
        return $token;
    }

    protected function createDeliveryHttpHandler(): callable
    {
        return (new ReflectionMethod(Salesforce::class, '_withRequestDiagnostics'))->invoke($this, $this->transport);
    }
}

function salesforceDiagnosticMessages(int $offset): string
{
    return str_replace('\\/', '/', implode("\n", array_filter(array_column(array_slice(Craft::getLogger()->messages, $offset), 0),
        static fn($message) => is_string($message) && str_starts_with($message, 'Salesforce: Salesforce HTTP '))));
}

it('records actual automatic Salesforce token exchanges and API requests without bodies or queries', function (): void {
    $history = [];
    $transport = HandlerStack::create(new MockHandler([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'access_token' => 'private-new-token', 'instance_url' => 'https://8.8.8.8', 'expires_in' => 3600,
        ])),
        new Response(200, ['Content-Type' => 'application/json'], '{"records":[{"Name":"private-customer"}]}'),
    ]));
    $transport->push(Middleware::history($history));
    $integration = new SalesforceDiagnosticTransportFixture([
        'name' => 'Salesforce', 'handle' => 'salesforce', 'grant' => 'client_credentials',
        'clientId' => 'private-client-id', 'clientSecret' => 'private-client-secret', 'authDomain' => 'https://8.8.8.8',
    ]);
    $integration->transport = $transport;
    $offset = count(Craft::getLogger()->messages);
    $result = $integration->request('GET', 'query', ['query' => ['q' => "SELECT Name FROM Lead WHERE Email='private-email'"]]);
    $logs = salesforceDiagnosticMessages($offset);
    expect($result)->toBe(['records' => [['Name' => 'private-customer']]])
        ->and($history)->toHaveCount(2)
        ->and($logs)->toContain('https://8.8.8.8/services/oauth2/token', 'https://8.8.8.8/services/data/v49.0/query', '"grant_type":"client_credentials"', '"httpStatus":200', '"queryObject":"Lead"')
        ->not->toContain('private-', 'SELECT', 'Authorization');
    expect(substr_count($logs, 'Salesforce HTTP request:'))->toBe(2)
        ->and(substr_count($logs, 'Salesforce HTTP response:'))->toBe(2);
});

it('retains Salesforce write results and failures while logging statuses and masking record IDs', function (string $outcome): void {
    $request = new Request('PATCH', 'https://8.8.8.8/services/data/v49.0/sobjects/Lead/private-record-id?private-query=1', ['Authorization' => 'Bearer private-token'], 'private-body');
    $response = match ($outcome) {
        'success' => new Response(204),
        'permission' => new Response(403, [], '[{"errorCode":"INSUFFICIENT_ACCESS","message":"private-detail"}]'),
        'transport' => new ConnectException('private-transport-detail', $request),
    };
    $integration = new Salesforce(['name' => 'Salesforce']);
    $handler = (new ReflectionMethod($integration, '_withRequestDiagnostics'))->invoke($integration, HandlerStack::create(new MockHandler([$response])));
    $offset = count(Craft::getLogger()->messages);
    try {
        $result = $handler($request, ['http_errors' => true])->wait();
        expect($outcome)->toBe('success')->and($result)->toBe($response);
    } catch (Throwable $error) {
        expect($outcome)->not->toBe('success');
        if ($outcome === 'transport') {
            expect($error)->toBe($response);
        } else {
            expect($error)->toBeInstanceOf(\GuzzleHttp\Exception\RequestException::class)
                ->and($error->getResponse())->toBe($response);
        }
    }
    $logs = salesforceDiagnosticMessages($offset);
    expect($logs)->toContain('"method":"PATCH"', '/sobjects/Lead/')
        ->not->toContain('private-', 'Authorization', 'INSUFFICIENT_ACCESS');
    expect($logs)->toContain(match ($outcome) {
        'success' => '"httpStatus":204',
        'permission' => '"httpStatus":403',
        'transport' => '"httpStatus":null',
    });
})->with(['success', 'permission', 'transport']);
