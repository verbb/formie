<?php

declare(strict_types=1);

use verbb\formie\base\Integration;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\helpers\References;
use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\integrations\crm\Pardot;
use verbb\formie\integrations\crm\Salesforce;
use verbb\formie\models\IntegrationField;

it('maps selected option values through direct and batch CRM paths', function (string $class, string $method, array $config, mixed $selected, string $expected): void {
    $form = formie()->form()->$method('answer', $config)->create();
    $submission = formie()->submission($form)->with(['answer' => $selected])->save();
    $field = $form->getFieldByHandle('answer');
    $reference = References::field($field->reference ?? $field->handle);
    $integration = new $class(['name' => 'Mapping fixture', 'handle' => 'mappingFixture']);
    $destination = new IntegrationField(['handle' => 'answer', 'type' => IntegrationField::TYPE_ARRAY]);

    expect($integration->getMappedFieldValue($reference, $submission, $destination))->toBe($expected)
        ->and($integration->getFieldMappingValues($submission, ['answer' => $reference], [$destination]))->toBe(['answer' => $expected]);
})->with([HubSpot::class, Salesforce::class, Pardot::class])->with([
    'dropdown' => ['dropdownField', ['options' => [['label' => 'One', 'value' => 'one'], ['label' => 'Unused', 'value' => 'unused']]], 'one', 'one'],
    'checkboxes' => ['checkboxesField', ['options' => [['label' => 'One', 'value' => 'one'], ['label' => 'Two', 'value' => 'two'], ['label' => 'Unused', 'value' => 'unused']]], ['one', 'two'], 'one;two'],
    'zero option' => ['checkboxesField', ['options' => [['label' => 'Zero', 'value' => '0'], ['label' => 'One', 'value' => 'one']]], ['0', 'one'], '0;one'],
    'recipient' => ['recipientsField', ['displayType' => 'dropdown', 'options' => [['label' => 'Sales', 'value' => 'sales@example.test'], ['label' => 'Unused', 'value' => 'unused@example.test']]], 'sales@example.test', 'sales@example.test'],
]);

it('retains mapping event replacements and raw values in outgoing payloads', function (): void {
    $form = formie()->form()->singleLineTextField('answer')->create();
    $submission = formie()->submission($form)->with(['answer' => 'Before'])->save();
    $field = $form->getFieldByHandle('answer');
    $integration = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubspot']);
    $seen = [];
    $integration->on(Integration::EVENT_MODIFY_FIELD_MAPPING_VALUE, function (ModifyFieldIntegrationValueEvent $event) use (&$seen): void {
        $seen[] = $event->rawValue;
        $event->value = 'After';
    });
    $settings = [new IntegrationField(['handle' => 'answer', 'type' => IntegrationField::TYPE_STRING])];
    foreach ([References::field($field->reference ?? $field->handle), 'Literal'] as $mapping) {
        expect($integration->getFieldMappingValues($submission, ['answer' => $mapping], $settings))->toBe(['answer' => 'After']);
    }
    expect($seen)->toBe(['Before', 'Literal']);
});
