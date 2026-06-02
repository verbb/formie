<?php

declare(strict_types=1);

use verbb\formie\helpers\References;
use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\models\IntegrationField;
use Tests\Support\IntegrationTestHelper;
use verbb\formie\helpers\ArrayHelper;

it('HubSpot EVENT_MODIFY_FIELD_MAPPING_VALUE converts boolean to string true/false', function (): void {
    $form = formie()->form(['title' => 'HubSpot Boolean'])
        ->agreeField('agree', [])
        ->create();
    $submission = formie()->submission($form)->with(['agree' => true])->save();
    IntegrationTestHelper::primeVariableCacheForSubmission($submission);

    $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', 'agree');
    $ref = $field->reference ?? 'agree';

    $integration = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubspot']);
    $integrationField = new IntegrationField(['type' => IntegrationField::TYPE_BOOLEAN]);
    $value = $integration->getMappedFieldValue(References::field($ref), $submission, $integrationField);

    expect($value)->toBeIn(['true', 'false']);
});

it('HubSpot EVENT_MODIFY_FIELD_MAPPING_VALUE converts TYPE_ARRAY to semicolon-joined string', function (): void {
    $form = formie()->form(['title' => 'HubSpot Array'])
        ->checkboxesField('topics', ['options' => [['label' => 'A', 'value' => 'a'], ['label' => 'B', 'value' => 'b']]])
        ->create();
    $submission = formie()->submission($form)->with(['topics' => ['a', 'b']])->save();
    IntegrationTestHelper::primeVariableCacheForSubmission($submission);

    $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', 'topics');
    $ref = $field->reference ?? 'topics';

    $integration = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubspot']);
    $integrationField = new IntegrationField(['type' => IntegrationField::TYPE_ARRAY]);
    $value = $integration->getMappedFieldValue(References::field($ref), $submission, $integrationField);

    expect($value)->toBeString();
    expect($value)->toContain(';');
});

it('HubSpot EVENT_MODIFY_FIELD_MAPPING_VALUE converts TYPE_DATE to timestamp milliseconds', function (): void {
    $form = formie()->form(['title' => 'HubSpot Date'])->dateField('dob')->create();
    $submission = formie()->submission($form)->with(['dob' => '2026-01-15'])->save();
    IntegrationTestHelper::primeVariableCacheForSubmission($submission);

    $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', 'dob');
    $ref = $field->reference ?? 'dob';

    $integration = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubspot']);
    $integrationField = new IntegrationField(['type' => IntegrationField::TYPE_DATE]);
    $value = $integration->getMappedFieldValue(References::field($ref), $submission, $integrationField);

    expect($value)->toBeString();
    expect((int) $value)->toBeGreaterThan(1700000000000);
    expect((int) $value)->toBeLessThan(2000000000000);
});

it('HubSpot EVENT_MODIFY_FIELD_MAPPING_VALUE converts TYPE_DATETIME to timestamp milliseconds', function (): void {
    $form = formie()->form(['title' => 'HubSpot Datetime'])->dateField('dob')->create();
    $submission = formie()->submission($form)->with(['dob' => '2026-01-15 12:00:00'])->save();
    IntegrationTestHelper::primeVariableCacheForSubmission($submission);

    $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', 'dob');
    $ref = $field->reference ?? 'dob';

    $integration = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubspot']);
    $integrationField = new IntegrationField(['type' => IntegrationField::TYPE_DATETIME]);
    $value = $integration->getMappedFieldValue(References::field($ref), $submission, $integrationField);

    expect($value)->toBeString();
    expect((int) $value)->toBeGreaterThan(1700000000000);
});

it('HubSpot convertValueForIntegration is not overridden (all logic in event)', function (): void {
    $integrationField = new IntegrationField(['type' => IntegrationField::TYPE_STRING]);
    $value = HubSpot::convertValueForIntegration('hello', $integrationField);
    expect($value)->toBe('hello');

    $integrationField = new IntegrationField(['type' => IntegrationField::TYPE_NUMBER]);
    $value = HubSpot::convertValueForIntegration('42', $integrationField);
    expect($value)->toBe(42);
});
