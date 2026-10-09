<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\events\MicrosoftDynamics365EntitiesEvent;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\integrations\crm\MicrosoftDynamics365;
use verbb\formie\models\FormIntegration;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\MicrosoftDynamics365Entity;

class CustomEntityDynamicsFixture extends MicrosoftDynamics365
{
    public array $deliveries = [];

    public function request(string $method, string $uri, array $options = [], bool $decodeJson = true): mixed
    {
        if (str_starts_with($uri, "EntityDefinitions(LogicalName='new_event')")) {
            return [
                'EntitySetName' => 'new_events',
                'PrimaryIdAttribute' => 'new_eventid',
            ];
        }

        return parent::request($method, $uri, $options, $decodeJson);
    }

    public function deliverPayload(Submission $submission, string $endpoint, mixed $payload, string $method = 'POST', string $contentType = 'json'): mixed
    {
        $this->deliveries[] = compact('endpoint', 'payload');
        preg_match('/\$select=([a-zA-Z][a-zA-Z0-9_]*)$/', $endpoint, $matches);

        return [($matches[1] ?? 'id') => 'entity-123'];
    }

    public function executeCustomPayload(Submission $submission): IntegrationResult
    {
        return $this->executePayload($submission);
    }
}

function addDynamicsEventEntity(MicrosoftDynamics365 $integration, array $config = []): void
{
    $integration->on(MicrosoftDynamics365::EVENT_MODIFY_ENTITIES, function(MicrosoftDynamics365EntitiesEvent $event) use ($config): void {
        $event->entities['event'] = new MicrosoftDynamics365Entity(array_merge([
            'label' => 'Event',
            'pluralLabel' => 'Events',
            'logicalName' => 'new_event',
            'entitySetName' => 'new_events',
            'primaryIdAttribute' => 'new_eventid',
        ], $config));
    });
}

function primeDynamicsConfig(MicrosoftDynamics365 $integration, array $config): void
{
    $integration->cache = [
        'config' => (new IntegrationConfig($config, $integration->getIntegrationConfigKey(), time()))->toStorage(),
    ];
}

it('registers custom entities through the same subclass and event pipeline', function(): void {
    $integration = new class(['name' => 'Dynamics', 'handle' => 'dynamics']) extends MicrosoftDynamics365 {
        protected function defineEntities(): array
        {
            return parent::defineEntities() + [
                'subclass' => [
                    'label' => 'Subclass Entity',
                    'logicalName' => 'new_subclass',
                ],
            ];
        }
    };
    addDynamicsEventEntity($integration);

    expect($integration->getEntities())
        ->toHaveKeys(['contact', 'lead', 'opportunity', 'account', 'incident', 'subclass', 'event'])
        ->and($integration->getEntities()['subclass'])->toBeInstanceOf(MicrosoftDynamics365Entity::class)
        ->and($integration->getEntities()['event']->getMappingSetting())->toBe('customEntitySettings.event.fieldMapping');
});

it('adds event entities to the form schema under the annotated custom settings container', function(): void {
    $integration = new MicrosoftDynamics365(['name' => 'Dynamics', 'handle' => 'dynamics']);
    addDynamicsEventEntity($integration);
    $form = formie()->form()->create();
    $schema = $integration->getFormSettingsSchema($form);
    $fieldsByName = array_column($schema, null, 'name');

    expect($fieldsByName)->toHaveKeys([
        'customEntitySettings.event.enabled',
        'customEntitySettings.event.fieldMapping',
    ]);

    FormIntegration::validateSchema($integration, SchemaHelper::compileSchema($schema));

    $runtime = FormIntegration::fromSettings($integration, [
        'enabled' => true,
        'customEntitySettings' => [
            'event' => [
                'enabled' => true,
                'fieldMapping' => ['new_name' => 'Conference'],
            ],
        ],
    ])->createRuntime();

    expect($runtime->customEntitySettings['event']['enabled'])->toBeTrue()
        ->and($runtime->customEntitySettings['event']['fieldMapping']['new_name'])->toBe('Conference');
});

it('validates and delivers event entities with provider metadata', function(): void {
    $integration = new CustomEntityDynamicsFixture(['name' => 'Dynamics', 'handle' => 'dynamics']);
    addDynamicsEventEntity($integration, [
        'entitySetName' => null,
        'primaryIdAttribute' => null,
    ]);
    primeDynamicsConfig($integration, [
        'event' => [
            new IntegrationField([
                'handle' => 'new_name',
                'name' => 'Name',
                'required' => true,
            ]),
        ],
    ]);
    $integration->enabled = true;
    $integration->customEntitySettings = [
        'event' => [
            'enabled' => true,
            'fieldMapping' => ['new_name' => 'Conference'],
        ],
    ];
    $integration->setScenario(MicrosoftDynamics365::SCENARIO_FORM);

    expect($integration->validate(['customEntitySettings']))->toBeTrue();

    $form = formie()->form()->create();
    $submission = formie()->submission($form)->save();
    $result = $integration->executeCustomPayload($submission);

    expect($result->isSuccessful())->toBeTrue()
        ->and($integration->deliveries)->toHaveCount(1)
        ->and($integration->deliveries[0]['endpoint'])->toBe('new_events?$select=new_eventid')
        ->and($integration->deliveries[0]['payload'])->toBe(['new_name' => 'Conference']);
});

it('preserves built-in entity delivery order and relationships through the registry', function(): void {
    $integration = new CustomEntityDynamicsFixture(['name' => 'Dynamics', 'handle' => 'dynamics']);
    primeDynamicsConfig($integration, [
        'contact' => [],
        'account' => [],
        'lead' => [],
        'opportunity' => [],
        'incident' => [],
    ]);
    $integration->mapToContact = true;
    $integration->mapToAccount = true;
    $integration->mapToLead = true;
    $integration->mapToOpportunity = true;
    $integration->mapToIncident = true;

    $form = formie()->form()->create();
    $submission = formie()->submission($form)->save();
    $result = $integration->executeCustomPayload($submission);

    expect($result->isSuccessful())->toBeTrue()
        ->and(array_column($integration->deliveries, 'endpoint'))->toBe([
            'contacts?$select=contactid',
            'accounts?$select=accountid',
            'leads?$select=leadid',
            'opportunities?$select=opportunityid',
            'incidents?$select=incidentid',
        ])
        ->and($integration->deliveries[1]['payload']['primarycontactid@odata.bind'])->toBe('contacts(entity-123)')
        ->and($integration->deliveries[2]['payload']['parentaccountid@odata.bind'])->toBe('accounts(entity-123)')
        ->and($integration->deliveries[3]['payload']['parentcontactid@odata.bind'])->toBe('contacts(entity-123)')
        ->and($integration->deliveries[3]['payload']['parentaccountid@odata.bind'])->toBe('accounts(entity-123)')
        ->and($integration->deliveries[4]['payload']['customerid_contact@odata.bind'])->toBe('contacts(entity-123)');
});

it('exposes custom entity picklists through the registered option source', function(): void {
    $integration = new MicrosoftDynamics365(['name' => 'Dynamics', 'handle' => 'dynamics']);
    addDynamicsEventEntity($integration);
    primeDynamicsConfig($integration, [
        'event' => [
            new IntegrationField([
                'handle' => 'new_category',
                'name' => 'Category',
                'options' => [
                    'label' => 'Category',
                    'options' => [
                        ['label' => 'Conference', 'value' => '100'],
                    ],
                ],
            ]),
        ],
    ]);

    $config = $integration->getOptionSourceBuilderConfig('dynamics365-picklists');
    $result = $integration->resolveOptionSourceOptions('dynamics365-picklists', [
        'collectionId' => 'event',
        'remoteHandle' => 'new_category',
    ]);

    expect($config['collectionOptions'][0]['value'])->toBe('event')
        ->and($result->error)->toBeNull()
        ->and($result->items[0]['value'])->toBe('100');
});
