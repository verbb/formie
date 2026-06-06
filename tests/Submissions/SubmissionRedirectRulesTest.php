<?php

declare(strict_types=1);

use craft\elements\Entry;
use verbb\formie\conditions\ConditionOperator;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SubmissionRedirectRulesHelper;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;

it('overrides the default submit action when a redirect rule matches', function (): void {
    $form = formie()
        ->form(['title' => 'Redirect Rule Match'])
        ->singleLineTextField('tier')
        ->create();

    $form->settings->setAttributes([
        'submitAction' => 'message',
        'submitActionMessage' => 'Thanks for submitting.',
        'enableRedirectRules' => true,
        'redirectRules' => [[
            'redirectType' => 'url',
            'submitActionUrl' => 'https://example.test/vip-thanks',
            'conditions' => [
                'conditionRule' => 'all',
                'conditions' => [[
                    'field' => 'tier',
                    'condition' => ConditionOperator::EQ,
                    'value' => 'vip',
                ]],
            ],
        ]],
    ], false);

    expect(\Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('tier', 'vip');
    $form->setCurrentSubmission($submission);

    expect($form->settings->getEffectiveSubmitAction($submission))->toBe('url')
        ->and($form->getRedirectUrl())->toContain('example.test/vip-thanks');
});

it('uses the default submit action when redirect rules are disabled', function (): void {
    $form = formie()
        ->form(['title' => 'Redirect Rules Disabled'])
        ->singleLineTextField('tier')
        ->create();

    $form->settings->setAttributes([
        'submitAction' => 'message',
        'enableRedirectRules' => false,
        'redirectRules' => [[
            'redirectType' => 'url',
            'submitActionUrl' => 'https://example.test/vip-thanks',
            'conditions' => [
                'conditionRule' => 'all',
                'conditions' => [[
                    'field' => 'tier',
                    'condition' => ConditionOperator::EQ,
                    'value' => 'vip',
                ]],
            ],
        ]],
    ], false);

    expect(\Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('tier', 'vip');
    $form->setCurrentSubmission($submission);

    expect($form->settings->getEffectiveSubmitAction($submission))->toBe('message')
        ->and($form->getRedirectUrl())->toBe('');
});

it('uses the default submit action when no redirect rules match', function (): void {
    $form = formie()
        ->form(['title' => 'Redirect Rule No Match'])
        ->singleLineTextField('tier')
        ->create();

    $form->settings->setAttributes([
        'submitAction' => 'url',
        'submitActionUrl' => 'https://example.test/default-thanks',
        'enableRedirectRules' => true,
        'redirectRules' => [[
            'redirectType' => 'url',
            'submitActionUrl' => 'https://example.test/vip-thanks',
            'conditions' => [
                'conditionRule' => 'all',
                'conditions' => [[
                    'field' => 'tier',
                    'condition' => ConditionOperator::EQ,
                    'value' => 'vip',
                ]],
            ],
        ]],
    ], false);

    expect(\Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('tier', 'standard');
    $form->setCurrentSubmission($submission);

    expect($form->settings->getEffectiveSubmitAction($submission))->toBe('url')
        ->and($form->getRedirectUrl())->toContain('example.test/default-thanks');
});

it('uses the first matching redirect rule', function (): void {
    $form = formie()
        ->form(['title' => 'Redirect Rule Order'])
        ->singleLineTextField('tier')
        ->create();

    $form->settings->setAttributes([
        'submitAction' => 'message',
        'enableRedirectRules' => true,
        'redirectRules' => [
            [
                'redirectType' => 'url',
                'submitActionUrl' => 'https://example.test/first',
                'conditions' => [
                    'conditionRule' => 'all',
                    'conditions' => [[
                        'field' => 'tier',
                        'condition' => ConditionOperator::EQ,
                        'value' => 'vip',
                    ]],
                ],
            ],
            [
                'redirectType' => 'url',
                'submitActionUrl' => 'https://example.test/second',
                'conditions' => [
                    'conditionRule' => 'all',
                    'conditions' => [[
                        'field' => 'tier',
                        'condition' => ConditionOperator::EQ,
                        'value' => 'vip',
                    ]],
                ],
            ],
        ],
    ], false);

    expect(\Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('tier', 'vip');
    $form->setCurrentSubmission($submission);

    expect($form->getRedirectUrl())->toContain('example.test/first')
        ->and($form->getRedirectUrl())->not->toContain('example.test/second');
});

it('resolves entry redirect rules', function (): void {
    $entry = Entry::find()->status(null)->slug('formie-seed-entry')->one();
    expect($entry)->not->toBeNull();

    $form = formie()
        ->form(['title' => 'Redirect Rule Entry'])
        ->singleLineTextField('tier')
        ->create();

    $form->settings->setAttributes([
        'submitAction' => 'message',
        'enableRedirectRules' => true,
        'redirectRules' => [[
            'redirectType' => 'entry',
            'submitActionEntry' => [[
                'id' => $entry->id,
                'siteId' => $entry->siteId,
            ]],
            'conditions' => [
                'conditionRule' => 'all',
                'conditions' => [[
                    'field' => 'tier',
                    'condition' => ConditionOperator::EQ,
                    'value' => 'vip',
                ]],
            ],
        ]],
    ], false);

    expect(\Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('tier', 'vip');
    $form->setCurrentSubmission($submission);

    expect($form->settings->getEffectiveSubmitAction($submission))->toBe('entry')
        ->and((string)$form->getRedirectUrl())->toContain('formie-seed-entry');
});

it('returns the default message action from redirect rule workflow responses', function (): void {
    $form = formie()
        ->form(['title' => 'Redirect Rule Message Default'])
        ->singleLineTextField('tier')
        ->create();

    $form->settings->setAttributes([
        'submitAction' => 'message',
        'submitActionMessage' => 'Thanks for submitting.',
        'enableRedirectRules' => true,
        'redirectRules' => [[
            'redirectType' => 'url',
            'submitActionUrl' => 'https://example.test/vip-thanks',
            'conditions' => [
                'conditionRule' => 'all',
                'conditions' => [[
                    'field' => 'tier',
                    'condition' => ConditionOperator::EQ,
                    'value' => 'vip',
                ]],
            ],
        ]],
    ], false);

    expect(\Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('tier', 'standard');

    $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));

    expect($response->success)->toBeTrue()
        ->and(SubmissionRedirectRulesHelper::getEffectiveSubmitAction($form, $response->submission))->toBe('message');
});
