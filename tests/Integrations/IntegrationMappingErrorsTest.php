<?php

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\helpers\DeliveryDiagnostics;
use verbb\formie\helpers\IntegrationApiErrors;
use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\integrations\emailmarketing\CampaignMonitor;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationResult;
use verbb\formie\references\ReferenceException;

it('identifies failed mappings without exposing defaults or transform arguments', function() {
    $form = formie()->form()->singleLineTextField('answer')->create();
    $submission = formie()->submission($form)->save();
    $integration = new HubSpot();
    $destination = new IntegrationField(['name' => 'Secondary phone', 'handle' => 'PhoneSecondary']);
    $slot = ['kind' => 'reference', 'value' => '{field:missing.number;transform=replace;search=private-search;replace=private-replacement|private-default}'];

    try {
        $integration->resolveMappedFieldValue($slot, $submission, $destination);
        $this->fail('A broken mapping must fail.');
    } catch (IntegrationException $error) {
        expect($error->getMessage())->toBe('Could not resolve mapping for “Secondary phone (PhoneSecondary)” ({field:missing.number}): Reference resolution failed: missingField.')
            ->and($error->getPrevious())->toBeInstanceOf(ReferenceException::class);
        expect(DeliveryDiagnostics::encode(DeliveryDiagnostics::exception($error)))->not->toContain('private-search', 'private-replacement', 'private-default');
    }

    // A failed structured slot must not leak into the next resolution on the same instance.
    expect($integration->resolveMappedFieldValue(['kind' => 'literal', 'value' => 'Next value'], $submission, $destination))->toBe('Next value');
});

it('names dynamic mapping destinations and aborts the batch', function() {
    $form = formie()->form()->singleLineTextField('answer')->create();
    $submission = formie()->submission($form)->save();
    $integration = new HubSpot();
    $resolved = [];
    $integration->on(Integration::EVENT_MODIFY_FIELD_MAPPING_VALUE, function($event) use (&$resolved) {
        $resolved[] = $event->integrationField->handle;
    });

    expect(fn() => $integration->getFieldMappingValues($submission, ['Good' => 'ok', 'PhoneSecondary' => '{field:missing}', 'Later' => 'not reached']))
        ->toThrow(IntegrationException::class, 'mapping for “PhoneSecondary” ({field:missing})');
    expect($resolved)->toBe(['Good']);
});

it('omits unsafe mapping content from failure context', function(array $slot, string $private) {
    $form = formie()->form()->singleLineTextField('answer')->create();
    $submission = formie()->submission($form)->save();
    $integration = new HubSpot();
    $destination = new IntegrationField(['handle' => 'Destination']);
    $integration->on(Integration::EVENT_MODIFY_FIELD_MAPPING_VALUE, fn() => throw new RuntimeException('Projection failed.'));

    try {
        $integration->resolveMappedFieldValue($slot, $submission, $destination);
        $this->fail('Expected a mapping failure.');
    } catch (IntegrationException $error) {
        expect($error->getMessage())->toContain('mapping for “Destination”')->not->toContain($private);
    }
})->with([
    'literal' => [['kind' => 'literal', 'value' => 'private-literal'], 'private-literal'],
    'interpolated text' => [['kind' => 'text', 'value' => 'private-text {field:missing}'], 'private-text'],
    'environment source' => [['kind' => 'reference', 'value' => '{environment:PRIVATE_ENV_NAME}'], 'PRIVATE_ENV_NAME'],
    'invalid slot' => [['kind' => 'invalid', 'value' => 'private-invalid'], 'private-invalid'],
    'unsafe field identity' => [['kind' => 'reference', 'value' => '{field:private%40example.test}'], 'private'],
]);

it('redacts configured credentials from mapping destinations and references', function() {
    $form = formie()->form()->singleLineTextField('answer')->create();
    $submission = formie()->submission($form)->save();
    $integration = new CampaignMonitor(['apiKey' => 'private-api-key']);

    expect(fn() => $integration->resolveMappedFieldValue('{field:private-api-key}', $submission, new IntegrationField(['handle' => 'private-api-key'])))
        ->toThrow(IntegrationException::class, 'mapping for “[redacted]” ({field:[redacted]})');
});

it('keeps Campaign Monitor delivery failed and includes mapping context in the handled error', function() {
    $form = formie()->form()->emailField('email')->create();
    $submission = formie()->submission($form)->with(['email' => 'person@example.test'])->save();
    $integration = new class(['name' => 'Campaign Monitor', 'listId' => 'fixture']) extends CampaignMonitor {
        public int $requests = 0;
        public function attempt(Submission $submission): IntegrationResult { return $this->executePayload($submission); }
        public function request(string $method, string $uri, array $options = []): mixed {
            $this->requests++;
            throw new RuntimeException('A broken mapping must not reach the provider.');
        }
    };
    $fields = array_map(fn($handle) => new IntegrationField(['handle' => $handle]), ['Email', 'PhoneSecondary']);
    $integration->cache['config'] = (new IntegrationConfig(['lists' => [new IntegrationCollection(['id' => 'fixture', 'fields' => $fields])]], $integration->getIntegrationConfigKey(), time()))->toStorage();
    $integration->fieldMapping = ['Email' => '{field:email}', 'PhoneSecondary' => '{field:missing.number}'];
    $settings = Formie::$plugin->getSettings();
    $previous = $settings->integrationApiErrorHandling;
    $settings->integrationApiErrorHandling = [['severity' => IntegrationApiErrors::SEVERITY_FAILURE, 'action' => IntegrationApiErrors::ACTION_FAIL_QUEUE]];

    try {
        expect(fn() => $integration->attempt($submission))->toThrow(IntegrationException::class, 'Could not resolve mapping for “PhoneSecondary” ({field:missing.number}): Reference resolution failed: missingField.');
        expect($integration->requests)->toBe(0);
    } finally {
        $settings->integrationApiErrorHandling = $previous;
    }
});
