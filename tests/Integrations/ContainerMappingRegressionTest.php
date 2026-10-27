<?php

use verbb\formie\integrations\emailmarketing\CampaignMonitor;
use verbb\formie\fields\Name;
use verbb\formie\fields\Date;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationConfig;

it('maps whole container values without treating the token as a child path', function(string $method, array $settings, mixed $input, string $expected) {
    $definition = $method === 'nameField' ? new Name($settings) : new Date($settings);
    $settings['rows'] = $definition->getSubFields();
    $form = formie()->form()->$method('answer', $settings)->create();
    $submission = formie()->submission($form)->with(['answer' => $input])->save();
    $field = $form->getFieldByHandle('answer');
    $integration = new CampaignMonitor(['listId' => 'fixture']);
    $destination = new IntegrationField(['handle' => 'result', 'type' => $method === 'dateField' && !isset($settings['collectMode']) ? IntegrationField::TYPE_DATE : IntegrationField::TYPE_STRING]);
    $integration->cache['config'] = (new IntegrationConfig(['lists' => [new IntegrationCollection(['id' => 'fixture', 'fields' => [$destination]])]], $integration->getIntegrationConfigKey(), time()))->toStorage();

    foreach ([$field->handle, $field->reference] as $identifier) {
        $slot = ['kind' => 'reference', 'value' => '{field:' . $identifier . '}'];
        expect($integration->resolveMappedFieldValue($slot, $submission, $destination))->toBe($expected)
            ->and($integration->getFieldMappingValues($submission, ['result' => $slot], [$destination]))->toBe(['result' => $expected]);
    }
})->with([
    'name' => ['nameField', ['useMultipleFields' => true], ['firstName' => 'Jane', 'lastName' => 'Doe'], 'Jane Doe'],
    'calendar date' => ['dateField', ['displayType' => 'calendar', 'includeTime' => false], ['date' => '2026-10-27'], '2026-10-27'],
    'date range' => ['dateField', ['displayType' => 'datePicker', 'collectMode' => Date::COLLECT_RANGE, 'dateFormat' => 'Y-m-d', 'includeTime' => false], ['startDate' => '2026-10-27', 'endDate' => '2026-10-28'], '2026-10-27 – 2026-10-28'],
]);

it('falls back safely when an integration requests an unknown container child', function() {
    $form = formie()->form()->nameField('person', ['useMultipleFields' => true, 'rows' => (new Name())->getSubFields()])->create();
    $submission = formie()->submission($form)->with(['person' => ['firstName' => 'Jane', 'lastName' => 'Doe']])->save();
    $field = $form->getFieldByHandle('person');
    $value = $submission->getFieldValue('person');
    $destination = new IntegrationField(['type' => IntegrationField::TYPE_STRING]);
    $integration = new CampaignMonitor();

    expect($field->getValueForIntegration($value, $destination, $integration, $submission, 'missing'))->toBe('Jane Doe')
        ->and($field->getValueForIntegration($value, $destination, $integration, $submission, 'firstName'))->toBe('Jane');
});

it('projects whole Name fields selected from repeated rows', function() {
    $form = formie()->form()->repeaterField('people', ['rows' => [['fields' => [[
        'type' => Name::class,
        'handle' => 'person',
        'label' => 'Person',
        'useMultipleFields' => true,
        'rows' => (new Name())->getSubFields(),
    ]]]]])->create();
    $submission = formie()->submission($form)->with(['people' => [
        ['person' => ['firstName' => 'Jane', 'lastName' => 'Doe']],
        ['person' => ['firstName' => 'John', 'lastName' => 'Smith']],
    ]])->save();
    $field = $form->getFieldByHandle('people')->getFieldByHandle('person');
    $integration = new CampaignMonitor();
    $destination = new IntegrationField(['type' => IntegrationField::TYPE_STRING]);

    expect($integration->getMappedFieldValue('{field:' . $field->reference . ';scope=all}', $submission, $destination))->toBe(['Jane Doe', 'John Smith'])
        ->and($integration->getMappedFieldValue('{field:' . $field->reference . ';scope=first}', $submission, $destination))->toBe('Jane Doe');
});
