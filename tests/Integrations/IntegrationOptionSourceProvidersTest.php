<?php

declare(strict_types=1);

use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\integrations\crm\MicrosoftDynamics365;
use verbb\formie\integrations\crm\Salesforce;
use verbb\formie\integrations\crm\Zoho;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\options\IntegrationOptionSourceHelper;

function primeIntegrationSettings(object $integration, IntegrationFormSettings $settings): void
{
    $integration->cache = [
        'settings' => $settings->serialize(),
    ];
}

it('exposes separate HubSpot integration source types', function (): void {
    $definitions = HubSpot::getOptionSourceDefinitions();

    expect($definitions)->toHaveCount(2)
        ->and(array_column($definitions, 'handle'))->toBe(['hubspot-forms', 'hubspot-properties'])
        ->and(array_column($definitions, 'label'))->toBe(['Form Fields', 'CRM Properties']);
});

it('registers integration option source providers for top integrations', function (): void {
    expect(Mailchimp::getOptionSourceDefinitions())->toHaveCount(1)
        ->and(HubSpot::getOptionSourceDefinitions())->toHaveCount(2)
        ->and(Salesforce::getOptionSourceDefinitions())->toHaveCount(1)
        ->and(Zoho::getOptionSourceDefinitions())->toHaveCount(1)
        ->and(MicrosoftDynamics365::getOptionSourceDefinitions())->toHaveCount(1)
        ->and(IntegrationOptionSourceHelper::providerExists('mailchimp-interests'))->toBeTrue()
        ->and(IntegrationOptionSourceHelper::providerExists('hubspot-forms'))->toBeTrue()
        ->and(IntegrationOptionSourceHelper::providerExists('salesforce-picklists'))->toBeTrue();
});

it('exposes HubSpot form refresh params for integration and option source refresh', function (): void {
    $integration = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubspot']);

    expect($integration->getFormSettingsRefreshParams())->toBe(['refreshForms' => true])
        ->and($integration->getOptionSourceRefreshParams('hubspot-forms'))->toBe(['refreshForms' => true])
        ->and($integration->getOptionSourceRefreshParams('hubspot-properties'))->toBe([]);
});

it('resolves Mailchimp interest options from cached list settings', function (): void {
    $integration = new Mailchimp(['name' => 'Mailchimp', 'handle' => 'mailchimp']);

    primeIntegrationSettings($integration, new IntegrationFormSettings([
        'lists' => [
            new IntegrationCollection([
                'id' => 'list-1',
                'name' => 'Newsletter',
                'fields' => [
                    new IntegrationField([
                        'handle' => 'interestCategories',
                        'name' => 'Interest Categories',
                        'options' => [
                            'label' => 'Interests',
                            'options' => [
                                ['label' => 'Alpha', 'value' => '1'],
                                ['label' => 'Beta', 'value' => '2'],
                            ],
                        ],
                    ]),
                ],
            ]),
        ],
    ]));

    $result = $integration->resolveOptionSourceOptions('mailchimp-interests', [
        'collectionId' => 'list-1',
        'remoteHandle' => 'interestCategories',
    ]);

    expect($result->error)->toBeNull()
        ->and($result->items)->toHaveCount(2)
        ->and($result->items[0]['label'])->toBe('Alpha')
        ->and($result->items[1]['value'])->toBe('2');
});

it('resolves HubSpot form fields from cached form settings', function (): void {
    $integration = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubspot']);

    primeIntegrationSettings($integration, new IntegrationFormSettings([
        'forms' => [
            new IntegrationCollection([
                'id' => '123__abc',
                'name' => 'Contact Form',
                'fields' => [
                    new IntegrationField([
                        'handle' => 'lifecyclestage',
                        'name' => 'Lifecycle Stage',
                        'sourceType' => 'enumeration',
                        'options' => [
                            'label' => 'Lifecycle Stage',
                            'options' => [
                                ['label' => 'Lead', 'value' => 'lead'],
                                ['label' => 'Customer', 'value' => 'customer'],
                            ],
                        ],
                    ]),
                ],
            ]),
        ],
    ]));

    $result = $integration->resolveOptionSourceOptions('hubspot-forms', [
        'collectionId' => '123__abc',
        'remoteHandle' => 'lifecyclestage',
    ]);

    expect($result->error)->toBeNull()
        ->and($result->items)->toHaveCount(2)
        ->and($result->items[1]['value'])->toBe('customer');
});

it('resolves CRM object picklists from cached field mapping settings', function (): void {
    $integration = new Salesforce(['name' => 'Salesforce', 'handle' => 'salesforce']);

    primeIntegrationSettings($integration, new IntegrationFormSettings([
        'contact' => [
            new IntegrationField([
                'handle' => 'LeadSource',
                'name' => 'Lead Source',
                'sourceType' => 'picklist',
                'options' => [
                    'label' => 'Lead Source',
                    'options' => [
                        ['label' => 'Web', 'value' => 'Web'],
                        ['label' => 'Phone', 'value' => 'Phone'],
                    ],
                ],
            ]),
            new IntegrationField([
                'handle' => 'FirstName',
                'name' => 'First Name',
                'sourceType' => 'string',
            ]),
        ],
    ]));

    $config = $integration->getOptionSourceBuilderConfig('salesforce-picklists');
    $result = $integration->resolveOptionSourceOptions('salesforce-picklists', [
        'collectionId' => 'contact',
        'remoteHandle' => 'LeadSource',
    ]);

    expect($config['collectionOptions'])->toHaveCount(1)
        ->and($config['collectionOptions'][0]['value'])->toBe('contact')
        ->and($config['remoteHandleOptions'])->toHaveCount(1)
        ->and($config['remoteHandleOptions'][0]['value'])->toBe('LeadSource')
        ->and($result->error)->toBeNull()
        ->and($result->items[0]['value'])->toBe('Web');
});

it('returns builder warnings when cached integration settings are empty', function (): void {
    $integration = new Zoho(['name' => 'Zoho', 'handle' => 'zoho']);
    $integration->cache = [];

    $config = $integration->getOptionSourceBuilderConfig('zoho-picklists');

    expect($config['collectionOptions'] ?? [])->toBe([])
        ->and($config['warning'] ?? null)->not->toBeNull();

    $result = $integration->resolveOptionSourceOptions('zoho-picklists', [
        'collectionId' => 'contact',
        'remoteHandle' => 'Industry',
    ]);

    expect($result->error)->not->toBeNull()
        ->and($result->items)->toBe([]);
});

it('resolves Dynamics 365 picklist rows for validation ranges', function (): void {
    $integration = new MicrosoftDynamics365(['name' => 'Dynamics', 'handle' => 'dynamics365']);

    primeIntegrationSettings($integration, new IntegrationFormSettings([
        'contact' => [
            new IntegrationField([
                'handle' => 'industrycode',
                'name' => 'Industry',
                'sourceType' => 'Picklist',
                'options' => [
                    'label' => 'Industry',
                    'options' => [
                        ['label' => 'Technology', 'value' => '1'],
                        ['label' => 'Finance', 'value' => '2'],
                    ],
                ],
            ]),
        ],
    ]));

    $resolved = $integration->resolveOptionSourceOptions('dynamics365-picklists', [
        'collectionId' => 'contact',
        'remoteHandle' => 'industrycode',
    ]);
    $allowedValues = array_column($resolved->items, 'value');

    expect($allowedValues)->toBe(['1', '2'])
        ->and(in_array('9', $allowedValues, true))->toBeFalse();
});
