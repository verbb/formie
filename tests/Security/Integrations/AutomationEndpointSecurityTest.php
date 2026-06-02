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
