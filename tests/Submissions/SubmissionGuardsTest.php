<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\Settings;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;

function withSubmissionGuardsPostContext(callable $callback, array $bodyParams = []): mixed
{
    return WebRequestTestHelper::withWebRequestContext(function () use ($callback, $bodyParams): mixed {
        Craft::$app->getRequest()->setBodyParams(array_merge([
            'handle' => 'guard-test-form',
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
            'formStartedAt' => (string)((int)(microtime(true) * 1000) - 10000),
            'formieHoneypot' => '',
        ], $bodyParams));

        return $callback();
    }, [
        'method' => 'POST',
        'bodyParams' => [],
    ]);
}

function createGuardTestForm(): Form
{
    $form = new Form();
    $form->handle = 'guard-test-' . uniqid();
    $form->title = 'Guard Test';
    $form->uid = StringHelper::UUID();
    $form->setNotifications([]);

    return $form;
}

it('flags honeypot submissions as spam during browser form posts', function (): void {
    $form = createGuardTestForm();

    /** @var Settings $settings */
    $settings = Formie::$plugin->getSettings();
    $original = $settings->enableHoneypot;

    try {
        $settings->enableHoneypot = true;

        $reason = withSubmissionGuardsPostContext(function () use ($form): ?string {
            $submission = new Submission();
            $submission->setForm($form);

            return Formie::$plugin->getSubmissionGuards()->validateRequest(new SubmissionRequest([
                'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
                'form' => $form,
                'submission' => $submission,
                'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
                'requestToken' => 'honeypot-token-' . uniqid(),
            ]));
        }, [
            'handle' => $form->handle,
            'formieHoneypot' => 'bot-filled',
        ]);

        expect($reason)->toContain('Honeypot');
    } finally {
        $settings->enableHoneypot = $original;
    }
});

it('flags fast browser submissions when minimum submit time is enabled', function (): void {
    $form = createGuardTestForm();

    /** @var Settings $settings */
    $settings = Formie::$plugin->getSettings();
    $originalEnabled = $settings->enableMinimumSubmitTime;
    $originalSeconds = $settings->minimumSubmitTime;

    try {
        $settings->enableMinimumSubmitTime = true;
        $settings->minimumSubmitTime = 30;

        $reason = withSubmissionGuardsPostContext(function () use ($form): ?string {
            $submission = new Submission();
            $submission->setForm($form);

            return Formie::$plugin->getSubmissionGuards()->validateRequest(new SubmissionRequest([
                'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
                'form' => $form,
                'submission' => $submission,
                'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
                'requestToken' => 'min-time-token-' . uniqid(),
            ]));
        }, [
            'handle' => $form->handle,
            'formStartedAt' => (string)(int)(microtime(true) * 1000),
        ]);

        expect($reason)->toContain('too quickly');
    } finally {
        $settings->enableMinimumSubmitTime = $originalEnabled;
        $settings->minimumSubmitTime = $originalSeconds;
    }
});

it('consumes request tokens after a completed submit and blocks replay attempts', function (): void {
    $form = createGuardTestForm();
    $submissionGuards = Formie::$plugin->getSubmissionGuards();

    /** @var Settings $settings */
    $settings = Formie::$plugin->getSettings();
    $original = $settings->enableReplayProtection;

    try {
        $settings->enableReplayProtection = true;
        $requestToken = 'replay-token-' . uniqid();

        $replayReason = withSubmissionGuardsPostContext(function () use ($form, $requestToken): ?string {
            $submission = new Submission();
            $submission->setForm($form);

            return Formie::$plugin->getSubmissionGuards()->validateRequest(new SubmissionRequest([
                'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
                'form' => $form,
                'submission' => $submission,
                'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
                'requestToken' => $requestToken,
            ]));
        }, [
            'handle' => $form->handle,
        ]);

        expect($replayReason)->toBeNull();

        $submissionGuards->consumeReplayToken((string)$form->uid, $requestToken);

        expect($submissionGuards->isReplayTokenConsumed((string)$form->uid, $requestToken))->toBeTrue();

        $blockedReason = withSubmissionGuardsPostContext(function () use ($form, $requestToken): ?string {
            $submission = new Submission();
            $submission->setForm($form);

            return Formie::$plugin->getSubmissionGuards()->validateRequest(new SubmissionRequest([
                'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
                'form' => $form,
                'submission' => $submission,
                'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
                'requestToken' => $requestToken,
            ]));
        }, [
            'handle' => $form->handle,
        ]);

        expect($blockedReason)->toContain('already been used');
    } finally {
        $settings->enableReplayProtection = $original;
    }
});

it('skips submission guards when the request is not a browser form post', function (): void {
    $form = createGuardTestForm();

    /** @var Settings $settings */
    $settings = Formie::$plugin->getSettings();
    $originalHoneypot = $settings->enableHoneypot;
    $originalMinTime = $settings->enableMinimumSubmitTime;

    try {
        $settings->enableHoneypot = true;
        $settings->enableMinimumSubmitTime = true;

        $reason = WebRequestTestHelper::withWebRequestContext(function () use ($form): ?string {
            Craft::$app->getRequest()->setBodyParams([]);

            $submission = new Submission();
            $submission->setForm($form);

            return Formie::$plugin->getSubmissionGuards()->validateRequest(new SubmissionRequest([
                'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
                'form' => $form,
                'submission' => $submission,
                'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
                'requestToken' => 'skip-guards-token-' . uniqid(),
            ]));
        }, [
            'method' => 'POST',
        ]);

        expect($reason)->toBeNull();
    } finally {
        $settings->enableHoneypot = $originalHoneypot;
        $settings->enableMinimumSubmitTime = $originalMinTime;
    }
});

it('persists submission guard settings in the spam protection store', function (): void {
    Formie::$plugin->getSpamProtection()->saveValues(array_merge(
        Formie::$plugin->getSpamProtection()->getSettingsValues(),
        [
            'enableHoneypot' => false,
            'minimumSubmitTime' => 12,
            'enableReplayProtection' => false,
        ],
    ));

    $settings = new Settings();
    Formie::$plugin->getSpamProtection()->hydrateSettings($settings);

    expect($settings->enableHoneypot)->toBeFalse()
        ->and($settings->minimumSubmitTime)->toBe(12)
        ->and($settings->enableReplayProtection)->toBeFalse();
});
