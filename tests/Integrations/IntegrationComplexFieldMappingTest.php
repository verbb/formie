<?php

declare(strict_types=1);

use verbb\formie\fields\Address;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\subfields\NameFirst;
use verbb\formie\fields\subfields\NameLast;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\References;
use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\models\IntegrationField;

/**
 * Integration value resolution for complex (nested) fields: Address, Name (multi), Group.
 * Exercises {field:ref:selector} tokens and getMappedFieldValue via Variables::getFieldAndValueForReference
 * with submission getFieldValue(handle.selector) for nested paths.
 */
$groupRows = [[
    'fields' => [[
        'type' => SingleLineText::class,
        'handle' => 'innerText',
        'label' => 'Inner Text',
    ]],
]];

it('resolves Address subfield via {field:ref:selector} and getMappedFieldValue', function (): void {
    $form = formie()
        ->form(['title' => 'Complex Address'])
        ->addressField('shippingAddress', ['rows' => (new Address())->getSubFields()])
        ->create();

    $submission = formie()->submission($form)->with([
        'shippingAddress' => [
            'address1' => '123 Main St',
            'city' => 'Melbourne',
            'state' => 'VIC',
            'zip' => '3000',
            'country' => 'AU',
        ],
    ])->save();

    $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', 'shippingAddress');
    expect($field)->not->toBeNull();
    $ref = $field->reference ?? $field->handle;
    $token = References::field($ref, 'address1');

    $integration = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubspot']);
    $integrationField = new IntegrationField(['type' => IntegrationField::TYPE_STRING]);
    $value = $integration->getMappedFieldValue($token, $submission, $integrationField);

    expect($value)->toBe('123 Main St');
});

it('resolves Name (multi) subfield via {field:ref:selector} and getMappedFieldValue', function (): void {
    $nameRows = [[
        'fields' => [
            ['type' => NameFirst::class, 'handle' => 'firstName', 'label' => 'First Name', 'enabled' => true],
            ['type' => NameLast::class, 'handle' => 'lastName', 'label' => 'Last Name', 'enabled' => true],
        ],
    ]];
    $form = formie()
        ->form(['title' => 'Complex Name'])
        ->nameField('fullName', ['useMultipleFields' => true, 'rows' => $nameRows])
        ->create();

    $submission = formie()->submission($form)->with([
        'fullName' => [
            'firstName' => 'Jane',
            'lastName' => 'Doe',
        ],
    ])->save();

    $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', 'fullName');
    expect($field)->not->toBeNull();
    $ref = $field->reference ?? $field->handle;
    $token = References::field($ref, 'firstName');

    $integration = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubspot']);
    $integrationField = new IntegrationField(['type' => IntegrationField::TYPE_STRING]);
    $value = $integration->getMappedFieldValue($token, $submission, $integrationField);

    expect($value)->toBe('Jane');
});

it('resolves Group child field via {field:ref:selector} and getMappedFieldValue', function () use ($groupRows): void {
    $form = formie()
        ->form(['title' => 'Complex Group'])
        ->groupField('groupContent', ['rows' => $groupRows])
        ->create();

    $submission = formie()->submission($form)->with([
        'groupContent' => ['innerText' => 'Group Value'],
    ])->save();

    $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', 'groupContent');
    expect($field)->not->toBeNull();
    $ref = $field->reference ?? $field->handle;
    $token = References::field($ref, 'innerText');

    $integration = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubspot']);
    $integrationField = new IntegrationField(['type' => IntegrationField::TYPE_STRING]);
    $value = $integration->getMappedFieldValue($token, $submission, $integrationField);

    expect($value)->toBe('Group Value');
});
