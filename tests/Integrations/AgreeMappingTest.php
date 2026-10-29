<?php

use verbb\formie\elements\Submission;
use verbb\formie\helpers\References;
use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\integrations\emailmarketing\CampaignMonitor;
use verbb\formie\models\IntegrationField;

it('offers an explicit empty opt-in choice for CRM and email marketing integrations', function(string $provider) {
    $integration = new $provider();
    $schema = (new ReflectionMethod($integration, 'getOptInFieldSchema'))->invoke($integration);

    expect($schema['emptyOptionLabel'] ?? null)->toBe('Always Opt-in')
        ->and($schema['variableConfig']['types'])->toBe(['boolean']);
})->with([HubSpot::class, CampaignMonitor::class]);

it('preserves Agree boolean and configured text mappings and permits clearing opt-in', function(string $provider, bool $checked) {
    $form = formie()->form()->agreeField('consent', [
        'required' => false,
        'checkedValue' => 'Subscribed',
        'uncheckedValue' => 'Not subscribed',
    ])->create();
    $saved = formie()->submission($form)->with(['consent' => $checked])->save();
    $submission = Submission::find()->id($saved->id)->status(null)->one();
    $field = $submission->getForm()->getFieldByHandle('consent');
    $reference = References::field($field->reference);
    $integration = new $provider(['optInField' => $reference]);
    $mapping = ['result' => ['kind' => 'reference', 'value' => $reference]];

    foreach ([IntegrationField::TYPE_BOOLEAN => $provider === HubSpot::class ? ($checked ? 'true' : 'false') : $checked, IntegrationField::TYPE_STRING => $checked ? 'Subscribed' : 'Not subscribed'] as $type => $expected) {
        $destination = new IntegrationField(['handle' => 'result', 'type' => $type]);
        expect($integration->resolveMappedFieldValue($mapping['result'], $submission, $destination))->toBe($expected);
    }

    expect($integration->enforceOptInField($submission))->toBe($checked);
    $integration->optInField = '';
    expect($integration->enforceOptInField($submission))->toBeTrue();
})->with([HubSpot::class, CampaignMonitor::class])->with([true, false]);
