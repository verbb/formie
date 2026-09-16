<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\integrations\crm\Zoho;
use verbb\formie\models\IntegrationField;

it('sends Zoho picklist actual values instead of metadata IDs', function (): void {
    $integration = new Zoho(['name' => 'Zoho', 'handle' => 'zoho']);
    $field = new IntegrationField([
        'handle' => 'Lead_Source',
        'type' => IntegrationField::TYPE_STRING,
        'options' => [
            'label' => 'Lead Source',
            'options' => [
                [
                    'label' => 'Web Form',
                    'value' => '5725767000001537003',
                    'actualValue' => 'Web Form API Value',
                ],
            ],
        ],
    ]);

    $values = $integration->getFieldMappingValues(
        new Submission(),
        ['Lead_Source' => '5725767000001537003'],
        [$field],
    );

    expect($values)->toBe(['Lead_Source' => 'Web Form API Value']);
});

it('normalizes Zoho multi-select picklist IDs', function (): void {
    $integration = new Zoho(['name' => 'Zoho', 'handle' => 'zoho']);
    $field = new IntegrationField([
        'handle' => 'Interests',
        'type' => IntegrationField::TYPE_ARRAY,
        'options' => [
            'label' => 'Interests',
            'options' => [
                ['label' => 'One', 'value' => '1001', 'actualValue' => 'one'],
                ['label' => 'Two', 'value' => '1002', 'actualValue' => 'two'],
            ],
        ],
    ]);

    $method = new ReflectionMethod(Zoho::class, '_getPickListPayloadValue');
    $method->setAccessible(true);

    expect($method->invoke($integration, ['1001', '1002'], $field))->toBe(['one', 'two']);
});

it('uses the visible label for legacy cached Zoho metadata', function (): void {
    $integration = new Zoho(['name' => 'Zoho', 'handle' => 'zoho']);
    $field = new IntegrationField([
        'options' => [
            'label' => 'Lead Source',
            'options' => [
                ['label' => 'Web Form', 'value' => '5725767000001537003'],
            ],
        ],
    ]);

    $method = new ReflectionMethod(Zoho::class, '_getPickListPayloadValue');
    $method->setAccessible(true);

    expect($method->invoke($integration, '5725767000001537003', $field))->toBe('Web Form');
});

it('retains Zoho picklist IDs for stable settings while recording their API values', function (): void {
    $integration = new Zoho(['name' => 'Zoho', 'handle' => 'zoho']);
    $method = new ReflectionMethod(Zoho::class, '_getCustomFields');
    $method->setAccessible(true);

    $fields = $method->invoke($integration, [[
        'api_name' => 'Lead_Source',
        'field_label' => 'Lead Source',
        'read_only' => false,
        'field_read_only' => false,
        'json_type' => 'string',
        'system_mandatory' => false,
        'pick_list_values' => [[
            'display_value' => 'Web Form',
            'actual_value' => 'Web Form API Value',
            'id' => '5725767000001537003',
        ]],
    ]]);

    expect($fields[0]->options['options'][0])->toBe([
        'label' => 'Web Form',
        'value' => '5725767000001537003',
        'actualValue' => 'Web Form API Value',
    ]);
});
