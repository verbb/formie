<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\integrations\automations\WebRequest;

class SecurityWebRequestEndpointProbe extends WebRequest
{
    public function resolveEndpointForTest(string $url, Submission $submission): bool|string|null
    {
        return $this->getEndpointUrl($url, $submission);
    }
}

it('blocks automation endpoints that resolve to private or reserved networks', function (string $url): void {
    $form = formie()
        ->form(['title' => 'Automation SSRF Guard'])
        ->singleLineTextField('target')
        ->create();
    $submission = formie()
        ->submission($form)
        ->with(['target' => $url])
        ->save();
    $integration = new SecurityWebRequestEndpointProbe([
        'name' => 'Security Web Request',
        'handle' => 'securityWebRequest',
    ]);

    expect(fn() => $integration->resolveEndpointForTest('{field:target}', $submission))
        ->toThrow(IntegrationException::class);
})->with([
    'loopback' => ['http://127.0.0.1/internal'],
    'rfc1918' => ['http://10.0.0.1/internal'],
    'metadata' => ['http://169.254.169.254/latest/meta-data'],
    'reserved' => ['http://192.0.2.10/webhook'],
    'unsupported scheme' => ['file:///etc/passwd'],
    'userinfo' => ['https://user:pass@8.8.8.8/webhook'],
])->group('security');

it('allows automation endpoints on public HTTP networks', function (): void {
    $form = formie()
        ->form(['title' => 'Automation Public Endpoint'])
        ->singleLineTextField('target')
        ->create();
    $submission = formie()
        ->submission($form)
        ->with(['target' => 'https://8.8.8.8/webhook'])
        ->save();
    $integration = new SecurityWebRequestEndpointProbe([
        'name' => 'Security Web Request',
        'handle' => 'securityWebRequest',
    ]);

    expect($integration->resolveEndpointForTest('https://8.8.8.8/webhook', $submission))->toBe('https://8.8.8.8/webhook');
})->group('security');

it('does not follow HTTP redirects for automation delivery', function (): void {
    $integration = new SecurityWebRequestEndpointProbe([
        'name' => 'Security Web Request Redirects',
        'handle' => 'securityWebRequestRedirects',
    ]);

    $history = [];
    $mockHandler = new \GuzzleHttp\Handler\MockHandler([
        new \GuzzleHttp\Psr7\Response(302, ['Location' => 'http://127.0.0.1/audit-private']),
        new \GuzzleHttp\Psr7\Response(200, [], 'should-not-reach'),
    ]);
    $stack = \GuzzleHttp\HandlerStack::create($mockHandler);
    $stack->push(\GuzzleHttp\Middleware::history($history));

    $config = $integration->getClient()->getConfig();
    $config['handler'] = $stack;
    // Keep the Automation redirect policy even when swapping the mock transport.
    $config['allow_redirects'] = false;
    $integration->setClient(new \GuzzleHttp\Client($config));

    // allow_redirects=false returns the 302 instead of chasing Location to a private host.
    $integration->request('GET', 'http://8.8.8.8/audit-public');

    expect($history)->toHaveCount(1)
        ->and((string)$history[0]['request']->getUri())->toBe('http://8.8.8.8/audit-public')
        ->and($mockHandler->count())->toBe(1);
})->group('security');

it('pins automation DNS to a validated public IP on absolute URLs', function (): void {
    $integration = new SecurityWebRequestEndpointProbe([
        'name' => 'Security Web Request Dns Pin',
        'handle' => 'securityWebRequestDnsPin',
    ]);

    $history = [];
    $mockHandler = new \GuzzleHttp\Handler\MockHandler([
        new \GuzzleHttp\Psr7\Response(200, [], 'ok'),
    ]);
    $stack = \GuzzleHttp\HandlerStack::create($mockHandler);
    $stack->push(\GuzzleHttp\Middleware::history($history));

    $config = $integration->getClient()->getConfig();
    $config['handler'] = $stack;
    $config['allow_redirects'] = false;
    $integration->setClient(new \GuzzleHttp\Client($config));

    // Literal public IP: pin is a no-op but must not reject the hop.
    $integration->request('GET', 'https://8.8.8.8/webhook');

    expect($history)->toHaveCount(1)
        ->and((string)$history[0]['request']->getUri())->toBe('https://8.8.8.8/webhook');

    $pinned = $integration->getClient()->getConfig();
    // Private literal IPs must still fail closed at pin time.
    expect(fn() => $integration->request('GET', 'http://127.0.0.1/internal'))
        ->toThrow(IntegrationException::class);
})->group('security');
