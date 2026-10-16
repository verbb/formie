<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use verbb\formie\elements\Submission;
use verbb\formie\enums\IntegrationStatus;
use verbb\formie\helpers\References;
use verbb\formie\integrations\crm\SuiteCrm;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\IntegrationSettingsContext;

class SuiteCrmContactPayloadFixture extends SuiteCrm
{
    public array $requests = [];
    public array $transactions = [];

    public function request(string $method, string $uri, array $options = []): mixed
    {
        $this->requests[] = compact('method', 'uri');

        if ($method === 'GET') {
            if ($uri !== 'meta/fields/Contacts') {
                throw new RuntimeException('Unexpected SuiteCRM metadata endpoint.');
            }

            // V8 omits name from vardefs: the surrounding object keys are the field handles.
            return ['data' => ['type' => 'fields', 'attributes' => [
                'first_name' => ['type' => 'varchar', 'required' => false],
                'last_name' => ['type' => 'varchar', 'required' => true],
                'email1' => ['type' => 'varchar', 'required' => false],
                'do_not_call' => ['type' => 'bool', 'required' => false],
                'score_c' => ['type' => 'int', 'required' => false],
            ]]];
        }

        $handler = HandlerStack::create(new MockHandler([
            new Response(201, ['Content-Type' => 'application/vnd.api+json'], '{"data":{"type":"Contacts","id":"contact-123"}}'),
        ]));
        $handler->push(Middleware::history($this->transactions));
        $client = new Client(['base_uri' => 'https://suitecrm.example.test/Api/V8/', 'handler' => $handler]);

        return json_decode((string)$client->request($method, $uri, $options)->getBody(), true);
    }

    public function executeForTest(Submission $submission): IntegrationResult
    {
        return $this->executePayload($submission);
    }
}

it('loads SuiteCRM V8 contact metadata for targeted and full refreshes', function(?string $dataKey): void {
    $integration = new SuiteCrmContactPayloadFixture([
        'mapToContact' => true,
        'settingsContext' => new IntegrationSettingsContext(['dataKey' => $dataKey]),
    ]);
    $fields = $integration->fetchConfig()->get('contact');

    expect($integration->requests)->toBe([['method' => 'GET', 'uri' => 'meta/fields/Contacts']])
        ->and(array_column($fields, 'handle'))->toBe(['first_name', 'last_name', 'email1', 'do_not_call', 'score_c'])
        ->and($fields[3]->type)->toBe(IntegrationField::TYPE_BOOLEAN)
        ->and($fields[4]->type)->toBe(IntegrationField::TYPE_NUMBER);
})->with(['contact', null]);

it('sends mapped SuiteCRM contact values as a V8 JSON API record', function(?string $dataKey): void {
    $form = formie()->form()->singleLineTextField('firstName')->singleLineTextField('lastName')->emailField('email')->create();
    $saved = formie()->submission($form)->with([
        'firstName' => 'John', 'lastName' => 'Doe', 'email' => 'john.doe@example.test',
    ])->save();
    $submission = Submission::find()->id($saved->id)->status(null)->one();
    $integration = new SuiteCrmContactPayloadFixture([
        'name' => 'SuiteCRM',
        'handle' => 'suiteCrm',
        'mapToContact' => true,
        'settingsContext' => new IntegrationSettingsContext(['dataKey' => $dataKey]),
        'contactFieldMapping' => [
            'first_name' => References::field($form->getFieldByHandle('firstName')->reference),
            'last_name' => References::field($form->getFieldByHandle('lastName')->reference),
            'email1' => References::field($form->getFieldByHandle('email')->reference),
            'do_not_call' => '0',
            'score_c' => '0',
            'unknown_field' => 'Must not be sent',
        ],
    ]);
    $integration->cache = ['config' => (new IntegrationConfig(
        $integration->fetchConfig()->all(), $integration->getIntegrationConfigKey(), time(),
    ))->toStorage()];

    $result = $integration->executeForTest($submission);

    expect($result->status)->toBe(IntegrationStatus::Succeeded)
        ->and($integration->transactions)->toHaveCount(1);
    $request = $integration->transactions[0]['request'];
    expect($request->getMethod())->toBe('POST')
        ->and((string)$request->getUri())->toBe('https://suitecrm.example.test/Api/V8/module')
        ->and($request->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and(json_decode((string)$request->getBody(), true))->toBe(['data' => [
            'type' => 'Contacts',
            'attributes' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email1' => 'john.doe@example.test',
                'do_not_call' => false,
                'score_c' => 0,
            ],
        ]]);
})->with(['contact', null]);

it('does not fetch SuiteCRM contacts for disabled or unrelated mappings', function(bool $enabled, ?string $dataKey): void {
    $integration = new SuiteCrmContactPayloadFixture([
        'mapToContact' => $enabled,
        'settingsContext' => new IntegrationSettingsContext(['dataKey' => $dataKey]),
    ]);

    expect($integration->fetchConfig()->all())->toBe([])
        ->and($integration->requests)->toBe([]);
})->with([[false, null], [false, 'contact'], [true, 'other']]);
