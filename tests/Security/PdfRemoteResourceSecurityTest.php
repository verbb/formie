<?php

declare(strict_types=1);

use verbb\formie\fields\MultiLineText;
use verbb\formie\helpers\PdfResourcePolicy;

use Dompdf\Options;

class PdfResourcePolicyDnsProbe extends PdfResourcePolicy
{
    public array $resolvedIps = [];

    protected function resolveHostIps(string $host): array
    {
        return $this->resolvedIps[$host] ?? [];
    }
}

it('blocks PDF resources on private and reserved networks', function (string $url): void {
    $policy = new PdfResourcePolicy();

    expect($policy->validateRemoteUri($url)[0])->toBeFalse();
})->with([
    'loopback' => ['http://127.0.0.1/internal.png'],
    'private' => ['http://10.0.0.1/internal.png'],
    'link-local metadata' => ['http://169.254.169.254/latest/meta-data'],
    'shared address space' => ['http://100.100.100.200/latest/meta-data'],
    'reserved' => ['http://192.0.2.10/image.png'],
    'multicast' => ['http://239.255.255.250/image.png'],
    'IPv6 loopback' => ['http://[::1]/image.png'],
    'IPv4-mapped IPv6' => ['http://[::ffff:127.0.0.1]/image.png'],
    'userinfo' => ['https://user:pass@8.8.8.8/image.png'],
    'unsupported scheme' => ['file:///etc/passwd'],
])->group('security');

it('allows PDF resources whose resolved addresses are all public', function (): void {
    $policy = new PdfResourcePolicyDnsProbe();
    $policy->resolvedIps['images.example.com'] = ['8.8.8.8', '2606:4700:4700::1111'];

    expect($policy->validateRemoteUri('https://images.example.com/photo.jpg')[0])->toBeTrue();
})->group('security');

it('blocks a hostname when any resolved address is private', function (): void {
    $policy = new PdfResourcePolicyDnsProbe();
    $policy->resolvedIps['mixed.example.com'] = ['8.8.8.8', '10.0.0.5'];

    expect($policy->validateRemoteUri('https://mixed.example.com/photo.jpg')[0])->toBeFalse();
})->group('security');

it('allows an explicitly trusted private PDF resource host', function (): void {
    $policy = new PdfResourcePolicy(['ASSETS.INTERNAL.EXAMPLE.']);

    expect($policy->validateRemoteUri('https://assets.internal.example/photo.jpg')[0])->toBeTrue();
})->group('security');

it('adds the PDF network rule without restoring protocols disabled by callers', function (): void {
    $options = new Options();
    $options->setIsRemoteEnabled(true);
    $options->setAllowedProtocols(['data://', 'https://']);
    $policy = new PdfResourcePolicy();
    $policy->apply($options);
    $protocols = $options->getAllowedProtocols();

    expect($protocols)->not->toHaveKey('http://')
        ->and($protocols)->toHaveKey('https://')
        ->and($protocols['https://']['rules'])->toHaveCount(2);
})->group('security');

it('strips images from new rich-text requests when the image control is disabled', function (): void {
    $field = new MultiLineText([
        'handle' => 'notes',
        'useRichText' => true,
        'richTextButtons' => ['bold', 'italic'],
    ]);
    $value = '<p>Hello <strong>there</strong></p><img src="http://169.254.169.254/latest/meta-data">';

    expect($field->normalizeValueFromRequest($value, null))
        ->toContain('<strong>there</strong>')
        ->not->toContain('<img')
        ->and($field->normalizeValue($value, null))->toContain('<img');
})->group('security');

it('preserves images in rich-text requests when the image control is enabled', function (): void {
    $field = new MultiLineText([
        'handle' => 'notes',
        'useRichText' => true,
        'richTextButtons' => ['bold', 'image'],
    ]);
    $value = '<p>Hello</p><img src="https://8.8.8.8/image.png" alt="Example">';

    expect($field->normalizeValueFromRequest($value, null))->toContain('<img');
})->group('security');
