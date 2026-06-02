<?php

declare(strict_types=1);

use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\References;
use verbb\formie\integrations\crm\Pipedrive;
use verbb\formie\models\IntegrationField;

it('Pipedrive EVENT_MODIFY_FIELD_MAPPING_VALUE casts set option arrays to integers', function (): void {
    $form = formie()->form(['title' => 'Pipedrive Set Casting'])
        ->checkboxesField('topics', ['options' => [['label' => 'One', 'value' => '1'], ['label' => 'Two', 'value' => '2']]])
        ->create();

    $submission = formie()->submission($form)->with(['topics' => ['1', '2']])->save();

    $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', 'topics');
    $ref = $field->reference ?? 'topics';

    $integration = new Pipedrive(['name' => 'Pipedrive', 'handle' => 'pipedrive']);
    $integrationField = new IntegrationField([
        'type' => IntegrationField::TYPE_ARRAY,
        'sourceType' => 'set',
    ]);

    $value = $integration->getMappedFieldValue(References::field($ref), $submission, $integrationField);

    expect($value)->toBeArray()
        ->and($value)->toHaveCount(2)
        ->and(is_int($value[0]))->toBeTrue()
        ->and(is_int($value[1]))->toBeTrue();
});

it('Pipedrive EVENT_MODIFY_FIELD_MAPPING_VALUE leaves non-set arrays unchanged', function (): void {
    $form = formie()->form(['title' => 'Pipedrive Non-Set'])
        ->checkboxesField('topics', ['options' => [['label' => 'One', 'value' => '1'], ['label' => 'Two', 'value' => '2']]])
        ->create();

    $submission = formie()->submission($form)->with(['topics' => ['1', '2']])->save();

    $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', 'topics');
    $ref = $field->reference ?? 'topics';

    $integration = new Pipedrive(['name' => 'Pipedrive', 'handle' => 'pipedrive']);
    $integrationField = new IntegrationField([
        'type' => IntegrationField::TYPE_ARRAY,
        'sourceType' => 'varchar',
    ]);

    $value = $integration->getMappedFieldValue(References::field($ref), $submission, $integrationField);

    expect($value)->toBeArray()
        ->and($value)->toHaveCount(2)
        ->and($value[0])->toBeArray()
        ->and($value[0])->toHaveKey('value')
        ->and($value[1])->toBeArray()
        ->and($value[1])->toHaveKey('value');
});
