<?php

declare(strict_types=1);

use verbb\formie\helpers\ClientEventsHelper;
use verbb\formie\models\FieldLayoutPageSettings;
use verbb\formie\services\SubmissionWorkflow;

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

    $resolved = ClientEventsHelper::resolveEvents($settings, $submission);

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

    $resolved = ClientEventsHelper::resolveEvents($settings, $submission);

    expect($resolved)->toHaveCount(2)
        ->and($resolved[0]['event'])->toBe('formStep')
        ->and($resolved[1]['event'])->toBe('generateLead')
        ->and($resolved[0]['payload']['plan'])->toBe('vip')
        ->and($resolved[1]['payload']['tier'])->toBe('vip');
});
