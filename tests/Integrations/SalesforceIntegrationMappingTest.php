<?php

declare(strict_types=1);

use DateTime;
use verbb\formie\base\Integration;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\References;
use verbb\formie\integrations\crm\Salesforce;
use verbb\formie\models\IntegrationField;
use yii\base\Event;

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

it('Salesforce EVENT_MODIFY_FIELD_MAPPING_VALUE formats DateTime raw values with T separator', function (): void {
    $integration = new Salesforce(['name' => 'Salesforce', 'handle' => 'salesforce']);
    $integrationField = new IntegrationField(['type' => IntegrationField::TYPE_DATETIME]);
    $event = new ModifyFieldIntegrationValueEvent([
        'integration' => $integration,
        'integrationField' => $integrationField,
        'rawValue' => new DateTime('2026-01-15 12:34:56'),
        'value' => '2026-01-15 12:34:56',
    ]);

    Event::trigger(Salesforce::class, Integration::EVENT_MODIFY_FIELD_MAPPING_VALUE, $event);

    expect($event->value)->toBe('2026-01-15T12:34:56');
});
