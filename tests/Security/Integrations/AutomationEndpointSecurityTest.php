<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\integrations\automations\WebRequest;
use verbb\formie\integrations\crm\Pardot;
use verbb\formie\integrations\messaging\Discord;
use verbb\formie\integrations\messaging\Slack;

class SecurityWebRequestEndpointProbe extends WebRequest
{
    public function resolveEndpointForTest(string $url, Submission $submission): bool|string|null
    {
        return $this->getEndpointUrl($url, $submission);
    }
}

class SecuritySlackPublicEndpointProbe extends Slack
{
    public array $history = [];

    public function request(string $method, string $uri, array $options = []): mixed
    {
        throw new RuntimeException('Authenticated provider requests must not handle form-configured webhooks.');
    }

    protected function createPublicEndpointClient(string $endpoint, array $config = []): \GuzzleHttp\Client
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new \GuzzleHttp\Psr7\Response(200, [], '{"ok":true}'),
        ]);
        $stack = \GuzzleHttp\HandlerStack::create($mockHandler);
        $stack->push(\GuzzleHttp\Middleware::history($this->history));

        return new \GuzzleHttp\Client([
            'handler' => $stack,
            'allow_redirects' => false,
        ]);
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
    'shared address space metadata' => ['http://100.100.100.200/latest/meta-data'],
    'benchmark network' => ['http://198.18.0.1/internal'],
    'multicast' => ['http://239.255.255.250/internal'],
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

it('allows public IPv6 literal endpoints', function (): void {
    $form = formie()
        ->form(['title' => 'Automation Public IPv6 Endpoint'])
        ->singleLineTextField('target')
        ->create();
    $submission = formie()->submission($form)->save();
    $integration = new SecurityWebRequestEndpointProbe([
        'name' => 'Security Web Request IPv6',
        'handle' => 'securityWebRequestIpv6',
    ]);

    expect($integration->resolveEndpointForTest('https://[2606:4700:4700::1111]/webhook', $submission))
        ->toBe('https://[2606:4700:4700::1111]/webhook');
})->group('security');

it('blocks private form-configured endpoints for native integrations', function (string $integrationClass): void {
    $integration = new $integrationClass([
        'name' => 'Form endpoint security probe',
        'handle' => 'formEndpointSecurityProbe',
    ]);

    expect(fn() => $integration->requestPublicEndpoint('POST', 'http://169.254.169.254/latest/meta-data/'))
        ->toThrow(IntegrationException::class);
})->with([
    'Pardot form handler' => [Pardot::class],
    'Discord webhook' => [Discord::class],
    'Slack webhook' => [Slack::class],
])->group('security');

it('sends Slack webhooks without the OAuth provider request path', function (): void {
    $form = formie()
        ->form(['title' => 'Slack public endpoint'])
        ->singleLineTextField('message')
        ->create();
    $submission = formie()
        ->submission($form)
        ->with(['message' => 'Hello'])
        ->save();
    $integration = new SecuritySlackPublicEndpointProbe([
        'name' => 'Slack public endpoint',
        'handle' => 'slackPublicEndpoint',
        'channelType' => Slack::TYPE_WEBHOOK,
        'webhook' => 'https://8.8.8.8/webhook',
        'message' => 'Hello',
    ]);

    expect($integration->sendPayload($submission))->toBeTrue()
        ->and($integration->history)->toHaveCount(1)
        ->and($integration->history[0]['request']->hasHeader('Authorization'))->toBeFalse();
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


it('rejects non-global and transition addresses before connecting', function (string $ip, bool $allowed): void {
    $integration = new SecurityWebRequestEndpointProbe(['name' => 'Address classification', 'handle' => 'addressClassification']);
    $method = new ReflectionMethod(\verbb\formie\base\Integration::class, '_isPublicEndpointIp');
    expect($method->invoke($integration, $ip))->toBe($allowed);
})->with([
    ['100.100.100.200', false], ['198.18.0.1', false], ['192.0.0.8', false],
    ['224.0.0.1', false], ['239.255.255.250', false], ['2001:db8::1', false],
    ['::ffff:127.0.0.1', false], ['64:ff9b::a00:1', false], ['2002:7f00:1::', false],
    ['ff02::1', false], ['8.8.8.8', true], ['1.1.1.1', true], ['2606:4700:4700::1111', true],
])->group('security');
