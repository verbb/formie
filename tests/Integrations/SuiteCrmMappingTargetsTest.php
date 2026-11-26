<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\enums\IntegrationStatus;
use verbb\formie\errors\IntegrationException;
use verbb\formie\helpers\References;
use verbb\formie\integrations\crm\SuiteCrm;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\IntegrationSettingsContext;

class SuiteCrmMappingTargetsFixture extends SuiteCrm
{
    // V8 omits name from vardefs: the surrounding object keys are the field handles.
    public const METADATA = [
        'Contacts' => [
            'last_name' => ['type' => 'varchar', 'required' => true],
            'email1' => ['type' => 'varchar'],
        ],
        'Leads' => [
            'last_name' => ['type' => 'varchar', 'required' => true],
            'email1' => ['type' => 'varchar'],
            'lead_source' => ['type' => 'enum'],
            'id' => ['type' => 'id', 'read_only' => true],
        ],
        'abc_Requests' => [
            'name' => ['type' => 'name', 'required' => true],
            'request_text_c' => ['type' => 'text'],
            'urgent_c' => ['type' => 'bool'],
        ],
        'xyz_Other' => [
            'other_c' => ['type' => 'varchar'],
        ],
    ];

    public array $requests = [];
    public array $transactions = [];

    public function request(string $method, string $uri, array $options = []): mixed
    {
        $this->requests[] = $method . ' ' . $uri;

        if ($method === 'GET') {
            $module = substr($uri, strlen('meta/fields/'));

            if (!str_starts_with($uri, 'meta/fields/') || !isset(self::METADATA[$module])) {
                throw new RuntimeException('Unexpected SuiteCRM metadata endpoint.');
            }

            return ['data' => ['type' => 'fields', 'attributes' => self::METADATA[$module]]];
        }

        $type = $options['json']['data']['type'] ?? '';
        $handler = HandlerStack::create(new MockHandler([
            new Response(201, ['Content-Type' => 'application/vnd.api+json'], json_encode(['data' => ['type' => $type, 'id' => strtolower($type) . '-1']])),
        ]));
        $handler->push(Middleware::history($this->transactions));
        $client = new Client(['base_uri' => 'https://suitecrm.example.test/Api/V8/', 'handler' => $handler]);

        return json_decode((string)$client->request($method, $uri, $options)->getBody(), true);
    }

    public function executeForTest(Submission $submission): IntegrationResult
    {
        return $this->executePayload($submission);
    }

    public function cacheConfig(array $config): void
    {
        $this->cache = ['config' => (new IntegrationConfig($config, $this->getIntegrationConfigKey(), time()))->toStorage()];
    }

    public function refreshForTest(?string $dataKey): array
    {
        $this->settingsContext = new IntegrationSettingsContext(['dataKey' => $dataKey]);
        $config = $this->fetchConfig()->all();
        $this->cacheConfig(array_merge($this->getConfig()->all(), $config));

        return $config;
    }
}

function suiteCrmTargetsSubmission(): array
{
    $form = formie()->form()->singleLineTextField('lastName')->emailField('email')->multiLineTextField('message')->create();
    $saved = formie()->submission($form)->with([
        'lastName' => 'Doe', 'email' => 'jane.doe@example.test', 'message' => 'Please call me back.',
    ])->save();

    return [$form, Submission::find()->id($saved->id)->status(null)->one()];
}

function suiteCrmSentRecords(SuiteCrmMappingTargetsFixture $integration): array
{
    return array_map(function($transaction) {
        $request = $transaction['request'];

        expect($request->getMethod())->toBe('POST')
            ->and((string)$request->getUri())->toBe('https://suitecrm.example.test/Api/V8/module');

        return json_decode((string)$request->getBody(), true)['data'];
    }, $integration->transactions);
}

function suiteCrmSchemaNode(array $schema, string $name): ?array
{
    foreach ($schema as $node) {
        if (!is_array($node)) {
            continue;
        }

        if (($node['name'] ?? null) === $name) {
            return $node;
        }

        if ($found = suiteCrmSchemaNode($node, $name)) {
            return $found;
        }
    }

    return null;
}

it('loads SuiteCRM Lead and custom module fields for targeted and full refreshes', function(?string $dataKey, array $requests): void {
    $integration = new SuiteCrmMappingTargetsFixture([
        'mapToContact' => true,
        'mapToLead' => true,
        'mapToModule' => true,
        'moduleName' => 'abc_Requests',
    ]);
    $config = $integration->refreshForTest($dataKey);

    expect($integration->requests)->toBe($requests);

    if (in_array('GET meta/fields/Leads', $requests, true)) {
        // Read-only fields can't be mapped.
        expect(array_column($config['lead'], 'handle'))->toBe(['last_name', 'email1', 'lead_source']);
    }

    if (in_array('GET meta/fields/abc_Requests', $requests, true)) {
        expect(array_column($config['modules'], 'id'))->toBe(['abc_Requests'])
            ->and(array_column($config['modules'][0]['fields'], 'handle'))->toBe(['name', 'request_text_c', 'urgent_c'])
            ->and($config['modules'][0]['fields'][2]->type)->toBe(IntegrationField::TYPE_BOOLEAN);
    }
})->with([
    'full refresh' => [null, ['GET meta/fields/Contacts', 'GET meta/fields/Leads', 'GET meta/fields/abc_Requests']],
    'lead' => ['lead', ['GET meta/fields/Leads']],
    'module' => ['module', ['GET meta/fields/abc_Requests']],
    'contact' => ['contact', ['GET meta/fields/Contacts']],
]);

it('keeps the fields of each SuiteCRM custom module that forms use', function(): void {
    $first = new SuiteCrmMappingTargetsFixture(['mapToModule' => true, 'moduleName' => 'abc_Requests']);
    $first->refreshForTest('module');

    // Another form using the same integration with a different module.
    $second = new SuiteCrmMappingTargetsFixture(['mapToModule' => true, 'moduleName' => 'xyz_Other', 'cache' => $first->cache]);
    $second->refreshForTest('module');

    // Refreshing the first module again replaces its fields rather than adding a second copy.
    $third = new SuiteCrmMappingTargetsFixture(['mapToModule' => true, 'moduleName' => 'abc_Requests', 'cache' => $second->cache]);
    $third->refreshForTest(null);

    $modules = $third->getConfig()->get('modules');

    expect(array_column($modules, 'id'))->toBe(['xyz_Other', 'abc_Requests'])
        ->and(array_column($modules[0]['fields'], 'handle'))->toBe(['other_c'])
        ->and(array_column($modules[1]['fields'], 'handle'))->toBe(['name', 'request_text_c', 'urgent_c']);
});

it('sends SuiteCRM Contact, Lead and custom module records in that order', function(): void {
    [$form, $submission] = suiteCrmTargetsSubmission();
    $lastName = References::field($form->getFieldByHandle('lastName')->reference);
    $email = References::field($form->getFieldByHandle('email')->reference);
    $message = References::field($form->getFieldByHandle('message')->reference);
    $integration = new SuiteCrmMappingTargetsFixture([
        'name' => 'SuiteCRM',
        'handle' => 'suiteCrm',
        'mapToContact' => true,
        'contactFieldMapping' => ['last_name' => $lastName, 'email1' => $email, 'unknown_field' => 'Must not be sent'],
        'mapToLead' => true,
        'leadFieldMapping' => ['last_name' => $lastName, 'email1' => $email, 'lead_source' => 'Web Site', 'request_text_c' => $message],
        'mapToModule' => true,
        'moduleName' => 'abc_Requests',
        'moduleFieldMapping' => ['name' => $lastName, 'request_text_c' => $message, 'urgent_c' => '0', 'email1' => $email],
    ]);
    $integration->refreshForTest(null);
    $integration->requests = [];

    $result = $integration->executeForTest($submission);

    expect($result->status)->toBe(IntegrationStatus::Succeeded)
        ->and($integration->requests)->toBe(['POST module', 'POST module', 'POST module'])
        ->and(suiteCrmSentRecords($integration))->toBe([
            ['type' => 'Contacts', 'attributes' => ['last_name' => 'Doe', 'email1' => 'jane.doe@example.test']],
            ['type' => 'Leads', 'attributes' => ['last_name' => 'Doe', 'email1' => 'jane.doe@example.test', 'lead_source' => 'Web Site']],
            ['type' => 'abc_Requests', 'attributes' => ['name' => 'Doe', 'request_text_c' => 'Please call me back.', 'urgent_c' => false]],
        ]);
});

it('limits SuiteCRM custom module values to the fields of the module the form uses', function(): void {
    [$form, $submission] = suiteCrmTargetsSubmission();
    $message = References::field($form->getFieldByHandle('message')->reference);
    $other = new SuiteCrmMappingTargetsFixture(['mapToModule' => true, 'moduleName' => 'abc_Requests']);
    $other->refreshForTest('module');

    $integration = new SuiteCrmMappingTargetsFixture([
        'name' => 'SuiteCRM',
        'handle' => 'suiteCrm',
        'mapToModule' => true,
        'moduleName' => 'xyz_Other',
        'moduleFieldMapping' => ['other_c' => $message, 'request_text_c' => $message],
        'cache' => $other->cache,
    ]);
    $integration->refreshForTest('module');

    expect($integration->executeForTest($submission)->status)->toBe(IntegrationStatus::Succeeded)
        ->and(suiteCrmSentRecords($integration))->toBe([
            ['type' => 'xyz_Other', 'attributes' => ['other_c' => 'Please call me back.']],
        ]);
});

it('sends SuiteCRM custom module values as they are before its fields are fetched', function(): void {
    [$form, $submission] = suiteCrmTargetsSubmission();
    $message = References::field($form->getFieldByHandle('message')->reference);
    $integration = new SuiteCrmMappingTargetsFixture([
        'name' => 'SuiteCRM',
        'handle' => 'suiteCrm',
        'mapToModule' => true,
        'moduleName' => 'abc_Requests',
        'moduleFieldMapping' => ['request_text_c' => $message],
    ]);

    expect($integration->executeForTest($submission)->status)->toBe(IntegrationStatus::Succeeded)
        ->and(suiteCrmSentRecords($integration))->toBe([
            ['type' => 'abc_Requests', 'attributes' => ['request_text_c' => 'Please call me back.']],
        ]);
});

it('rejects SuiteCRM custom module names that are not module names', function(?string $moduleName, string $error): void {
    [, $submission] = suiteCrmTargetsSubmission();
    $integration = new SuiteCrmMappingTargetsFixture([
        'name' => 'SuiteCRM',
        'handle' => 'suiteCrm',
        'enabled' => true,
        'mapToContact' => true,
        'mapToModule' => true,
        'moduleName' => $moduleName,
        'settingsContext' => new IntegrationSettingsContext(),
    ]);
    $integration->setScenario(Integration::SCENARIO_FORM);
    $integration->validate();

    expect($integration->getModuleName())->toBe('')
        ->and($integration->getErrors('moduleName'))->toBe([$error])
        ->and($integration->refreshForTest('module'))->toBe([])
        ->and($integration->requests)->toBe([]);

    // Nothing is sent, including the Contact, when the module can't be.
    expect(fn() => $integration->executeForTest($submission))->toThrow(IntegrationException::class, 'Invalid module name')
        ->and($integration->requests)->toBe([])
        ->and($integration->transactions)->toBe([]);
})->with([
    'path' => ['../Users', 'Module Name is invalid.'],
    'nested path' => ['Accounts/1', 'Module Name is invalid.'],
    'trailing newline' => ["abc_Requests\n", 'Module Name is invalid.'],
    'query' => ['Leads?filter=1', 'Module Name is invalid.'],
    'leading digit' => ['1abc', 'Module Name is invalid.'],
    'space' => ['abc Requests', 'Module Name is invalid.'],
    'blank' => [null, 'Module Name cannot be blank.'],
]);

it('gives the SuiteCRM custom module mapping the fields of each fetched module', function(): void {
    $form = formie()->form()->create();
    $first = new SuiteCrmMappingTargetsFixture(['mapToModule' => true, 'moduleName' => 'abc_Requests']);
    $first->refreshForTest('module');
    $integration = new SuiteCrmMappingTargetsFixture(['mapToModule' => true, 'moduleName' => 'xyz_Other', 'cache' => $first->cache]);
    $integration->refreshForTest('module');

    $schema = $integration->getFormSettingsSchema($form);
    $mapping = suiteCrmSchemaNode($schema, 'moduleFieldMapping');

    expect(suiteCrmSchemaNode($schema, 'mapToLead'))->not->toBeNull()
        ->and(suiteCrmSchemaNode($schema, 'leadFieldMapping')['dataKey'])->toBe('lead')
        ->and(suiteCrmSchemaNode($schema, 'moduleName')['if'])->toBe('mapToModule')
        ->and($mapping['dataKey'])->toBe('module')
        ->and($mapping['selectedCollectionField'])->toBe('moduleName')
        ->and(array_column($mapping['integrationFieldCollections'], 'id'))->toBe(['abc_Requests', 'xyz_Other'])
        ->and(array_column($mapping['integrationFieldCollections'][0]['fields'], 'handle'))->toBe(['name', 'request_text_c', 'urgent_c'])
        ->and(array_column($mapping['integrationFields'], 'handle'))->toBe(['other_c']);
});
