<?php

declare(strict_types=1);

use craft\elements\Entry;
use verbb\formie\conditions\ConditionOperator;
use verbb\formie\elements\Submission;
use verbb\formie\enums\CompletionBehavior;
use verbb\formie\helpers\SubmissionRedirectRulesHelper;
use verbb\formie\services\CompletionResolver;

beforeEach(function () { \verbb\formie\Formie::$plugin->getSettings()->completionRedirectAllowedOrigins = ['https://example.test', 'http://formie-react-tests.ddev.site']; });

it('overrides the default submit action when a redirect rule matches', function (): void {
    $form = formie()
        ->form(['title' => 'Redirect Rule Match'])
        ->singleLineTextField('tier')
        ->settings(['disableCaptchas' => true])
        ->create();

    $form->settings->setAttributes([
        'submitAction' => 'message',
        'successMessage' => 'Thanks for submitting.',
        'enableRedirectRules' => true,
        'redirectRules' => [[
            'redirectType' => 'url',
            'redirectUrl' => 'https://example.test/vip-thanks',
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

    $completion = (new CompletionResolver())->resolve($form, $submission, false);

    expect($completion->behavior)->toBe(CompletionBehavior::Redirect)
        ->and($completion->url)->toContain('example.test/vip-thanks');
});

it('uses the default submit action when redirect rules are disabled', function (): void {
    $form = formie()
        ->form(['title' => 'Redirect Rules Disabled'])
        ->singleLineTextField('tier')
        ->settings(['disableCaptchas' => true])
        ->create();

    $form->settings->setAttributes([
        'submitAction' => 'message',
        'enableRedirectRules' => false,
        'redirectRules' => [[
            'redirectType' => 'url',
            'redirectUrl' => 'https://example.test/vip-thanks',
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

    $completion = (new CompletionResolver())->resolve($form, $submission, false);

    expect($completion->behavior)->toBe(CompletionBehavior::Message)
        ->and($completion->url)->toBeNull();
});

it('uses the default submit action when no redirect rules match', function (): void {
    $form = formie()
        ->form(['title' => 'Redirect Rule No Match'])
        ->singleLineTextField('tier')
        ->settings(['disableCaptchas' => true])
        ->create();

    $form->settings->setAttributes([
        'submitAction' => 'url',
        'redirectUrl' => 'https://example.test/default-thanks',
        'enableRedirectRules' => true,
        'redirectRules' => [[
            'redirectType' => 'url',
            'redirectUrl' => 'https://example.test/vip-thanks',
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

    $completion = (new CompletionResolver())->resolve($form, $submission, false);

    expect($completion->behavior)->toBe(CompletionBehavior::Redirect)
        ->and($completion->url)->toContain('example.test/default-thanks');
});

it('uses the first matching redirect rule', function (): void {
    $form = formie()
        ->form(['title' => 'Redirect Rule Order'])
        ->singleLineTextField('tier')
        ->settings(['disableCaptchas' => true])
        ->create();

    $form->settings->setAttributes([
        'submitAction' => 'message',
        'enableRedirectRules' => true,
        'redirectRules' => [
            [
                'redirectType' => 'url',
                'redirectUrl' => 'https://example.test/first',
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
                'redirectUrl' => 'https://example.test/second',
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
        ->settings(['disableCaptchas' => true])
        ->create();

    $form->settings->setAttributes([
        'submitAction' => 'message',
        'enableRedirectRules' => true,
        'redirectRules' => [[
            'redirectType' => 'entry',
            'redirectEntry' => [[
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

    $completion = (new CompletionResolver())->resolve($form, $submission, false);

    expect($completion->behavior)->toBe(CompletionBehavior::Redirect)
        ->and($completion->url)->toContain('formie-seed-entry')
        ->and(SubmissionRedirectRulesHelper::getMatchedRule($form, $submission)['redirectType'] ?? null)->toBe('entry');
});

it('returns the default message action from redirect rule workflow responses', function (): void {
    $form = formie()
        ->form(['title' => 'Redirect Rule Message Default'])
        ->singleLineTextField('tier')
        ->settings(['disableCaptchas' => true])
        ->create();

    $form->settings->setAttributes([
        'submitAction' => 'message',
        'successMessage' => 'Thanks for submitting.',
        'enableRedirectRules' => true,
        'redirectRules' => [[
            'redirectType' => 'url',
            'redirectUrl' => 'https://example.test/vip-thanks',
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

    $response = runSubmissionCommand(submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
    ]));

    expect($response->success)->toBeTrue()
        ->and($response->outcome->data['completion']['behavior'] ?? null)->toBe(CompletionBehavior::Message->value);
});
