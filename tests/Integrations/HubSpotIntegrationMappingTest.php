<?php

declare(strict_types=1);

use verbb\formie\helpers\References;
use verbb\formie\base\Integration;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;
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

it('exposes every HubSpot marketing consent checkbox as a separate mapping field', function (): void {
    $integration = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubspot']);
    $method = new ReflectionMethod($integration, '_getFormFields');
    $method->setAccessible(true);

    $fields = $method->invoke($integration, [
        'formFieldGroups' => [],
        'metaData' => [[
            'name' => 'legalConsentOptions',
            'value' => json_encode([
                'processingConsentType' => 'REQUIRED_CHECKBOX',
                'communicationConsentCheckboxes' => [
                    [
                        'label' => '<strong>Marketing newsletter</strong>',
                        'communicationTypeId' => 101,
                    ],
                    [
                        'label' => 'One-to-one communication',
                        'communicationTypeId' => 202,
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ]],
    ]);

    $fieldsByHandle = [];

    foreach ($fields as $field) {
        $fieldsByHandle[$field->handle] = $field;
    }

    expect($fieldsByHandle)->toHaveKeys([
        'legalConsentOptionsMarketing',
        'legalConsentOptionsMarketing__202',
        'legalConsentOptionsProcessing',
    ])->and($fieldsByHandle['legalConsentOptionsMarketing']->name)->toBe('Legal Consent (Marketing) - Subscription 1')
        ->and($fieldsByHandle['legalConsentOptionsMarketing']->data)->toBe([
            'text' => 'Marketing newsletter',
            'typeId' => '101',
        ])->and($fieldsByHandle['legalConsentOptionsMarketing__202']->name)->toBe('Legal Consent (Marketing) - Subscription 2')
        ->and($fieldsByHandle['legalConsentOptionsMarketing__202']->data)->toBe([
            'text' => 'One-to-one communication',
            'typeId' => '202',
        ]);
});

it('keeps the existing mapping handle for a single HubSpot marketing consent', function (): void {
    $integration = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubspot']);
    $method = new ReflectionMethod($integration, '_getFormFields');
    $method->setAccessible(true);

    $fields = $method->invoke($integration, [
        'formFieldGroups' => [],
        'metaData' => [[
            'name' => 'legalConsentOptions',
            'value' => json_encode([
                'communicationConsentCheckboxes' => [[
                    'label' => 'Newsletter',
                    'communicationTypeId' => 101,
                ]],
            ], JSON_THROW_ON_ERROR),
        ]],
    ]);

    $marketingFields = array_values(array_filter($fields, fn(IntegrationField $field): bool => str_starts_with((string)$field->handle, 'legalConsentOptionsMarketing')));

    expect($marketingFields)->toHaveCount(1)
        ->and($marketingFields[0]->handle)->toBe('legalConsentOptionsMarketing')
        ->and($marketingFields[0]->name)->toBe('Legal Consent (Marketing)');
});

it('builds HubSpot communications for every accepted marketing consent', function (): void {
    $integration = new HubSpot([
        'name' => 'HubSpot',
        'handle' => 'hubspot',
        'formId' => '123__abc',
    ]);
    $integration->cache = [
        'settings' => [
            'forms' => [[
                'id' => '123__abc',
                'fields' => [
                    [
                        'handle' => 'legalConsentOptionsMarketing',
                        'data' => ['text' => 'Marketing newsletter', 'typeId' => '101'],
                    ],
                    [
                        'handle' => 'legalConsentOptionsMarketing__202',
                        'data' => ['text' => 'One-to-one communication', 'typeId' => '202'],
                    ],
                    [
                        'handle' => 'legalConsentOptionsMarketing__303',
                        'data' => ['text' => 'Product updates', 'typeId' => '303'],
                    ],
                ],
            ]],
        ],
    ];
    $formValues = [
        'email' => 'person@example.com',
        'legalConsentOptionsMarketing' => 'true',
        'legalConsentOptionsMarketing__202' => true,
        'legalConsentOptionsMarketing__303' => 'false',
    ];
    $method = new ReflectionMethod($integration, '_extractMarketingConsentCommunications');
    $method->setAccessible(true);
    $arguments = [&$formValues];

    $communications = $method->invokeArgs($integration, $arguments);

    expect($communications)->toBe([
        [
            'value' => true,
            'subscriptionTypeId' => '101',
            'text' => 'Marketing newsletter',
        ],
        [
            'value' => true,
            'subscriptionTypeId' => '202',
            'text' => 'One-to-one communication',
        ],
    ])->and($formValues)->toBe(['email' => 'person@example.com']);
});

it('includes a hardcoded marketing consent in the HubSpot form submission payload', function (): void {
    $form = formie()->form(['title' => 'HubSpot Static Marketing Consent'])
        ->emailField('email')
        ->create();
    $submission = formie()->submission($form)->with(['email' => 'person@example.com'])->save();
    IntegrationTestHelper::primeVariableCacheForSubmission($submission);

    $emailField = ArrayHelper::firstWhere($submission->getFields(), 'handle', 'email');
    $integration = new HubSpot([
        'name' => 'HubSpot',
        'handle' => 'hubspot',
        'mapToForm' => true,
        'formId' => '123__abc',
        'formFieldMapping' => [
            'email' => References::field($emailField->reference ?? 'email'),
            'legalConsentOptionsMarketing' => 'true',
        ],
    ]);
    $settings = new IntegrationFormSettings([
        'forms' => [
            new IntegrationCollection([
                'id' => '123__abc',
                'name' => 'Newsletter',
                'fields' => [
                    new IntegrationField(['handle' => 'email', 'name' => 'Email']),
                    new IntegrationField([
                        'handle' => 'legalConsentOptionsMarketing',
                        'name' => 'Legal Consent (Marketing)',
                        'type' => IntegrationField::TYPE_BOOLEAN,
                        'data' => ['text' => 'Agency Life Newsletter', 'typeId' => '456'],
                    ]),
                ],
            ]),
        ],
    ]);
    $integration->cache = ['settings' => $settings->serialize()];
    $capturedPayload = null;
    $integration->on(Integration::EVENT_BEFORE_SEND_PAYLOAD, function(SendIntegrationPayloadEvent $event) use (&$capturedPayload): void {
        $capturedPayload = $event->payload;
        $event->isValid = false;
    });

    expect($integration->sendPayload($submission))->toBeTrue()
        ->and($capturedPayload['legalConsentOptions']['consent']['communications'] ?? null)->toBe([
            [
                'value' => true,
                'subscriptionTypeId' => '456',
                'text' => 'Agency Life Newsletter',
            ],
        ]);
});
