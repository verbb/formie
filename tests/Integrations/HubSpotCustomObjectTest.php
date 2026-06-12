<?php

declare(strict_types=1);

use verbb\formie\integrations\crm\HubSpot;

function hubSpotWithCustomObjectSchemas(array $schemas): HubSpot
{
    $integration = new HubSpot([
        'name' => 'HubSpot',
        'handle' => 'hubspot',
    ]);

    $integration->cache = [
        'settings' => [
            'customObjectSchemas' => $schemas,
        ],
    ];

    return $integration;
}

it('resolves standard HubSpot object type aliases', function (): void {
    $integration = hubSpotWithCustomObjectSchemas([]);
    $method = new ReflectionMethod($integration, '_resolveHubSpotObjectTypeId');
    $method->setAccessible(true);

    expect($method->invoke($integration, 'CONTACT'))->toBe('0-1')
        ->and($method->invoke($integration, 'COMPANY'))->toBe('0-2')
        ->and($method->invoke($integration, 'DEAL'))->toBe('0-3')
        ->and($method->invoke($integration, 'TICKET'))->toBe('0-5');
});

it('passes through numeric HubSpot object type IDs', function (): void {
    $integration = hubSpotWithCustomObjectSchemas([]);
    $method = new ReflectionMethod($integration, '_resolveHubSpotObjectTypeId');
    $method->setAccessible(true);

    expect($method->invoke($integration, '2-21479350'))->toBe('2-21479350')
        ->and($method->invoke($integration, '0-1'))->toBe('0-1');
});

it('resolves custom object schemas by name and label', function (): void {
    $integration = hubSpotWithCustomObjectSchemas([
        [
            'objectTypeId' => '2-21479350',
            'name' => 'event_registrations',
            'fullyQualifiedName' => 'p1234_event_registrations',
            'labels' => [
                'singular' => 'Event Registration',
                'plural' => 'Event Registrations',
            ],
        ],
    ]);

    $method = new ReflectionMethod($integration, '_resolveHubSpotObjectTypeId');
    $method->setAccessible(true);

    expect($method->invoke($integration, 'event_registrations'))->toBe('2-21479350')
        ->and($method->invoke($integration, 'Event Registration'))->toBe('2-21479350')
        ->and($method->invoke($integration, 'Event Registrations'))->toBe('2-21479350');
});

it('prefixes custom object form fields with their object type ID', function (): void {
    $integration = hubSpotWithCustomObjectSchemas([
        [
            'objectTypeId' => '2-21479350',
            'name' => 'event_registrations',
            'labels' => [
                'singular' => 'Event Registration',
            ],
        ],
    ]);

    $method = new ReflectionMethod($integration, '_prepareHubSpotFormField');
    $method->setAccessible(true);

    $prepared = $method->invoke($integration, [
        'name' => 'submitter_firstname',
        'label' => 'Submitter First Name',
        'propertyObjectType' => 'Event Registration',
        'type' => 'string',
        'fieldType' => 'text',
    ]);

    expect($prepared['name'])->toBe('2-21479350.submitter_firstname')
        ->and($prepared['data']['objectTypeId'])->toBe('2-21479350')
        ->and($prepared['data']['propertyName'])->toBe('submitter_firstname');
});

it('keeps contact form fields unprefixed for backward compatibility', function (): void {
    $integration = hubSpotWithCustomObjectSchemas([]);

    $method = new ReflectionMethod($integration, '_prepareHubSpotFormField');
    $method->setAccessible(true);

    $prepared = $method->invoke($integration, [
        'name' => 'email',
        'label' => 'Email',
        'propertyObjectType' => 'CONTACT',
        'type' => 'string',
        'fieldType' => 'text',
    ]);

    expect($prepared['name'])->toBe('email')
        ->and($prepared['data']['objectTypeId'])->toBe('0-1');
});
