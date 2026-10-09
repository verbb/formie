<?php

use verbb\formie\integrations\elements\Entry;
use verbb\formie\integrations\emailmarketing\Campaign;
use verbb\formie\helpers\References;
use verbb\formie\models\IntegrationField;

it('formats element mappings once regardless of the number of provider instances', function (): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $submission = formie()->submission($form)->with(['message' => '&amp;amp;lt;'])->save();
    $integration = new Entry();
    $token = References::field($form->getFieldByHandle('message')->reference);
    $first = $integration->getMappedFieldValue($token, $submission, new IntegrationField());
    new Entry();
    new Entry();
    expect($integration->getMappedFieldValue($token, $submission, new IntegrationField()))->toBe($first);
});

it('formats campaign mappings once and still dispatches extension events', function (): void {
    $form = formie()->form()->multiLineTextField('message')->create();
    $submission = formie()->submission($form)->with(['message' => '&amp;amp;lt;'])->save();
    $integration = new Campaign();
    $token = References::field($form->getFieldByHandle('message')->reference);
    $first = $integration->getMappedFieldValue($token, $submission, new IntegrationField());
    new Campaign();
    new Campaign();
    $calls = 0;
    $integration->on(Campaign::EVENT_MODIFY_FIELD_MAPPING_VALUE, function ($event) use (&$calls): void { $calls++; $event->value .= '!'; });
    expect($integration->getMappedFieldValue($token, $submission, new IntegrationField()))->toBe($first . '!')
        ->and($calls)->toBe(1);
});
