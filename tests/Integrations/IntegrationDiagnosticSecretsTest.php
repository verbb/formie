<?php

use verbb\formie\attributes\Sensitive;
use verbb\formie\base\Integration;
use verbb\formie\integrations\crm\ActiveCampaign;
use verbb\formie\integrations\crm\Salesforce;

class LegacyDiagnosticCredentialsFixture extends Salesforce
{
    public string $apiToken = 'legacy-private-token';
    public array $credentials = ['username' => 'legacy-private-user', 'password' => 'legacy-private-password'];
    public string $credentialMode = '1';
    public string $endpointUrl = 'https://example.com/api';
}

it('does not derive diagnostic secrets from built-in endpoint settings or cached metadata', function (): void {
    $integration = new ActiveCampaign([
        'name' => 'ActiveCampaign',
        'apiKey' => 'private-api-key',
        'apiUrl' => 'https://example.com/api',
        'cache' => ['apiKey' => 'cached-field-value', 'headers' => ['mode' => '0']],
        'context' => ['password' => 'runtime-field-value'],
    ]);
    expect($integration->getDiagnosticSecrets())->toBe(['private-api-key']);
    $offset = count(Craft::getLogger()->messages);
    Integration::info($integration, 'Status 200; https://example.com/api; cached-field-value; runtime-field-value; private-api-key');
    $messages = array_filter(array_column(array_slice(Craft::getLogger()->messages, $offset), 0), static fn($message) => is_string($message) && str_starts_with($message, 'ActiveCampaign: '));
    expect(implode("\n", $messages))->toContain('Status 200; https://example.com/api; cached-field-value; runtime-field-value; [redacted]')
        ->not->toContain('private-api-key');
});

it('recognizes inherited legacy add-on credential properties without scanning unrelated properties', function (): void {
    $integration = new class([
        'name' => 'Legacy add-on',
        'useCredentials' => '0',
        'clientSecret' => 'private-client-secret',
        'cache' => ['token' => 'cached-field-value'],
    ]) extends LegacyDiagnosticCredentialsFixture {};

    expect($integration->getDiagnosticSecrets())->toContain('legacy-private-token', 'legacy-private-user', 'legacy-private-password', 'private-client-secret')
        ->not->toContain('0', '1', 'https://example.com/api', 'cached-field-value');
    $offset = count(Craft::getLogger()->messages);
    Integration::info($integration, 'Status 201; legacy-private-token; legacy-private-password; private-client-secret');
    $messages = array_filter(array_column(array_slice(Craft::getLogger()->messages, $offset), 0), static fn($message) => is_string($message) && str_starts_with($message, 'Legacy add-on: '));
    expect(implode("\n", $messages))->toContain('Status 201; [redacted]; [redacted]; [redacted]');
});

it('redacts environment references and resolved values from explicitly sensitive properties', function (): void {
    $previous = getenv('FORMIE_DIAGNOSTIC_TEST_SECRET');
    putenv('FORMIE_DIAGNOSTIC_TEST_SECRET=private-resolved-value');
    try {
        $integration = new class(['name' => 'Annotated add-on']) extends Salesforce {
            #[Sensitive]
            public string $opaque = '$FORMIE_DIAGNOSTIC_TEST_SECRET';
        };
        expect($integration->getDiagnosticSecrets())->toContain('$FORMIE_DIAGNOSTIC_TEST_SECRET', 'private-resolved-value');
        $offset = count(Craft::getLogger()->messages);
        Integration::info($integration, 'Status 200; $FORMIE_DIAGNOSTIC_TEST_SECRET; private-resolved-value');
        $messages = array_filter(array_column(array_slice(Craft::getLogger()->messages, $offset), 0), static fn($message) => is_string($message) && str_starts_with($message, 'Annotated add-on: '));
        expect(implode("\n", $messages))->toContain('Status 200; [redacted]; [redacted]');
    } finally {
        putenv($previous === false ? 'FORMIE_DIAGNOSTIC_TEST_SECRET' : 'FORMIE_DIAGNOSTIC_TEST_SECRET=' . $previous);
    }
});
