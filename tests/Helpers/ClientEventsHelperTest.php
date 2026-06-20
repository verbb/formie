<?php

declare(strict_types=1);

use verbb\formie\helpers\ClientEventsHelper;
use verbb\formie\models\FieldLayoutPageSettings;
use verbb\formie\models\FormSettings;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\Formie;

it('migrates legacy client event fields into structured events', function (): void {
    $legacy = ClientEventsHelper::migrateLegacyEventFields([
        ['label' => 'event', 'value' => 'leadGenerated'],
        ['label' => 'formHandle', 'value' => 'contact'],
        ['label' => 'email', 'value' => '{field:email}'],
    ]);

    expect($legacy)->toBe([[
        'event' => 'leadGenerated',
        'payload' => [
            ['key' => 'formHandle', 'value' => 'contact'],
            ['key' => 'email', 'value' => '{field:email}'],
        ],
    ]]);
});

it('resolves field references in client event payloads', function (): void {
    $form = formie()
        ->form(['title' => 'Client Events'])
        ->emailField('email')
        ->create();

    $emailField = $form->getFieldByHandle('email');
    $emailToken = verbb\formie\helpers\References::field((string)$emailField->reference);

    $submission = formie()->submission($form)->with([
        'email' => 'jane@example.com',
    ])->save();

    $settings = new FieldLayoutPageSettings([
        'enableClientEvents' => true,
        'clientEvents' => [[
            'event' => 'formSubmission',
            'payload' => [
                ['key' => 'email', 'value' => $emailToken],
                ['key' => 'formHandle', 'value' => '{form:handle}'],
            ],
        ]],
    ]);

    $resolved = ClientEventsHelper::resolveEventsFromSettings($settings, $submission);

    expect($resolved)->toHaveCount(1)
        ->and($resolved[0]['event'])->toBe('formSubmission')
        ->and($resolved[0]['payload']['event'])->toBe('formSubmission')
        ->and($resolved[0]['payload']['email'])->toBe('jane@example.com')
        ->and($resolved[0]['payload']['formHandle'])->toBe($form->handle);
});

it('skips client events for save actions', function (): void {
    $form = formie()
        ->form(['title' => 'Client Events Save'])
        ->create();

    $page = $form->getPages()[0];
    $settings = $page->getPageSettings();
    $settings->enableClientEvents = true;
    $settings->clientEvents = [[
        'event' => 'formPageSubmission',
        'payload' => [['key' => 'formHandle', 'value' => '{form.handle}']],
    ]];

    $submission = new verbb\formie\elements\Submission();
    $submission->setForm($form);

    expect(ClientEventsHelper::resolveForSubmittedPage(
        $form,
        $submission,
        (int)$page->id,
        SubmissionWorkflow::SUBMIT_ACTION_SAVE,
    ))->toBe([]);
});

it('resolves multiple configured client events', function (): void {
    $form = formie()
        ->form(['title' => 'Client Events Multi'])
        ->singleLineTextField('plan')
        ->create();

    $planField = $form->getFieldByHandle('plan');
    $planToken = verbb\formie\helpers\References::field((string)$planField->reference);

    $submission = formie()->submission($form)->with([
        'plan' => 'vip',
    ])->save();

    $settings = new FieldLayoutPageSettings([
        'enableClientEvents' => true,
        'clientEvents' => [
            [
                'event' => 'formStep',
                'payload' => [['key' => 'plan', 'value' => $planToken]],
            ],
            [
                'event' => 'generateLead',
                'payload' => [['key' => 'tier', 'value' => $planToken]],
            ],
        ],
    ]);

    $resolved = ClientEventsHelper::resolveEventsFromSettings($settings, $submission);

    expect($resolved)->toHaveCount(2)
        ->and($resolved[0]['event'])->toBe('formStep')
        ->and($resolved[1]['event'])->toBe('generateLead')
        ->and($resolved[0]['payload']['plan'])->toBe('vip')
        ->and($resolved[1]['payload']['tier'])->toBe('vip');
});

it('falls back to form default client events when a page has none configured', function (): void {
    $form = formie()
        ->form(['title' => 'Client Events Defaults'])
        ->create();

    $formSettings = $form->getSettings();
    $formSettings->enableDefaultClientEvents = true;
    $formSettings->defaultClientEvents = [[
        'event' => 'formPageSubmission',
        'payload' => [['key' => 'formHandle', 'value' => '{form:handle}']],
    ]];

    $page = $form->getPages()[0];
    $pageSettings = $page->getPageSettings();
    $pageSettings->enableClientEvents = true;
    $pageSettings->clientEvents = [];

    $submission = formie()->submission($form)->save();

    $resolved = ClientEventsHelper::resolveForSubmittedPage(
        $form,
        $submission,
        (int)$page->id,
        SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    );

    expect($resolved)->toHaveCount(1)
        ->and($resolved[0]['event'])->toBe('formPageSubmission')
        ->and($resolved[0]['payload']['formHandle'])->toBe($form->handle);
});

it('skips client events when conditions do not match', function (): void {
    $form = formie()
        ->form(['title' => 'Client Events Conditions'])
        ->singleLineTextField('plan')
        ->create();

    $planField = $form->getFieldByHandle('plan');
    $planToken = verbb\formie\helpers\References::field((string)$planField->reference);

    $submission = formie()->submission($form)->with([
        'plan' => 'basic',
    ])->save();

    $settings = new FieldLayoutPageSettings([
        'enableClientEvents' => true,
        'clientEvents' => [[
            'event' => 'generate_lead',
            'payload' => [['key' => 'plan', 'value' => $planToken]],
            'enableConditions' => true,
            'conditions' => [
                'conditionRule' => 'all',
                'conditions' => [[
                    'field' => $planToken,
                    'condition' => 'is',
                    'value' => 'vip',
                ]],
            ],
        ]],
    ]);

    expect(ClientEventsHelper::resolveEventsFromSettings($settings, $submission))->toBe([]);
});

it('materializes client event templates with field mappings', function (): void {
    $template = Formie::$plugin->getClientEventTemplates()->getTemplate('ga4-generate-lead');

    expect($template)->not->toBeNull();

    $event = Formie::$plugin->getClientEventTemplates()->materializeTemplate('ga4-generate-lead', [
        'email' => '{field:abc-123}',
    ]);

    $emailRow = null;
    $formIdRow = null;

    foreach ($event['payload'] as $row) {
        if ($row['key'] === 'email') {
            $emailRow = $row;
        }

        if ($row['key'] === 'form_id') {
            $formIdRow = $row;
        }
    }

    expect($event['event'])->toBe('generate_lead')
        ->and($emailRow['value'] ?? null)->toBe('{field:abc-123}')
        ->and($formIdRow['value'] ?? null)->toBe('{form:handle}');
});
