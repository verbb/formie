<?php

declare(strict_types=1);

use verbb\formie\base\Integration;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\References;
use verbb\formie\integrations\crm\Salesforce;
use verbb\formie\models\IntegrationField;

it('Salesforce getMappedFieldValue joins TYPE_ARRAY values with semicolons', function (): void {
    $form = formie()->form(['title' => 'Salesforce Array Mapping'])
        ->checkboxesField('topics', ['options' => [['label' => 'One', 'value' => 'one'], ['label' => 'Two', 'value' => 'two']]])
        ->create();

    $submission = formie()->submission($form)->with(['topics' => ['one', 'two']])->save();

    $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', 'topics');
    $ref = $field->reference ?? 'topics';

    $integration = new Salesforce(['name' => 'Salesforce', 'handle' => 'salesforce']);
    $integrationField = new IntegrationField(['type' => IntegrationField::TYPE_ARRAY]);

    $value = $integration->getMappedFieldValue(References::field($ref), $submission, $integrationField);

    expect($value)->toBeString()
        ->and($value)->toContain(';');
});

it('formats Salesforce date mappings before dispatching extension events', function (): void {
    $form = formie()->form()->dateField('appointment', ['timeFormat' => 'H:i:s'])->create();
    $submission = formie()->submission($form)->with(['appointment' => '2026-01-15T12:34:56+00:00'])->save();
    $integration = new Salesforce(['name' => 'Salesforce', 'handle' => 'salesforce']);
    $integrationField = new IntegrationField(['type' => IntegrationField::TYPE_DATETIME]);
    $captured = null;
    $integration->on(Integration::EVENT_MODIFY_FIELD_MAPPING_VALUE, function (ModifyFieldIntegrationValueEvent $event) use (&$captured): void {
        $captured = $event->value;
    });
    $token = References::field($form->getFieldByHandle('appointment')->reference);
    $value = $integration->getMappedFieldValue($token, $submission, $integrationField);
    expect($value)->toBe('2026-01-15T12:34:56.000Z')->and($captured)->toBe($value);
});
