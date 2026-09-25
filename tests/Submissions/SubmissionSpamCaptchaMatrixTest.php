<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\models\Settings;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\services\SubmissionWorkflow;

it('keeps spam behaviour contract for message and success modes when keyword spam checks are triggered', function (): void {
    $form = formie()
        ->form(['title' => 'Spam Matrix'])
        ->singleLineTextField('message')
        ->settings(['disableCaptchas' => true])
        ->create();

    /** @var Settings $settings */
    $settings = Formie::$plugin->getSettings();
    $originalKeywords = $settings->spamKeywords;
    $originalBehaviour = $settings->spamBehaviour;
    $originalMessage = $settings->spamBehaviourMessage;

    try {
        $settings->spamKeywords = 'blocked-keyword';
        $settings->spamBehaviourMessage = 'Blocked by spam matrix.';

        $process = new SubmissionWorkflow();

        $messageModeSubmission = new Submission();
        $messageModeSubmission->setForm($form);
        $messageModeSubmission->setFieldValueFromRequest('message', 'contains blocked-keyword content');

        $settings->spamBehaviour = Settings::SPAM_BEHAVIOUR_MESSAGE;
        $messageModeResponse = runSubmissionCommand(submissionCommand([
            'operationId' => Craft::$app->getSecurity()->generateRandomString(),
            'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT,
            'form' => $form,
            'submission' => $messageModeSubmission,
            'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
        ]));

        $successModeSubmission = new Submission();
        $successModeSubmission->setForm($form);
        $successModeSubmission->setFieldValueFromRequest('message', 'contains blocked-keyword content');

        $settings->spamBehaviour = Settings::SPAM_BEHAVIOUR_SUCCESS;
        $successModeResponse = runSubmissionCommand(submissionCommand([
            'operationId' => Craft::$app->getSecurity()->generateRandomString(),
            'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT,
            'form' => $form,
            'submission' => $successModeSubmission,
            'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
        ]));

        expect($messageModeResponse->success)->toBeFalse()
            ->and($messageModeResponse->submission->isSpam)->toBeTrue()
            ->and($messageModeResponse->submission->getErrors('form'))->not->toBeEmpty()
            ->and($successModeResponse->success)->toBeTrue()
            ->and($successModeResponse->submission->isSpam)->toBeTrue()
            ->and($successModeResponse->submission->getErrors('form'))->toBeEmpty();
    } finally {
        $settings->spamKeywords = $originalKeywords;
        $settings->spamBehaviour = $originalBehaviour;
        $settings->spamBehaviourMessage = $originalMessage;
    }
});

it('keeps non-spam submit success deterministic when no captcha integrations are enabled', function (): void {
    Formie::$plugin->getSettings()->spamKeywords = '';
    Formie::$plugin->getSettings()->enableSuspiciousTextDetection = false;
    Formie::$plugin->getSettings()->enableGlobalSubmissionThrottling = false;
    $form = formie()
        ->form(['title' => 'Captcha Matrix Baseline'])
        ->singleLineTextField('message')
        ->settings(['disableCaptchas' => true])
        ->create();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('message', 'safe-content');

    $response = runSubmissionCommand(submissionCommand([
        'operationId' => Craft::$app->getSecurity()->generateRandomString(),
            'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
    ]));

        expect($response->success)->toBeTrue()
        ->and($response->submission->isSpam)->toBeFalse();
});

it('applies show-success spam behaviour without persisting discarded spam submissions', function (): void {
    \Tests\Support\WebRequestTestHelper::withWebRequestContext(function (): void {
    $form = formie()
        ->form(['title' => 'Spam Discard Matrix'])
        ->singleLineTextField('message')
        ->settings(['disableCaptchas' => true])
        ->create();

    /** @var Settings $settings */
    $settings = Formie::$plugin->getSettings();
    $originalKeywords = $settings->spamKeywords;
    $originalBehaviour = $settings->spamBehaviour;
    $originalSaveSpam = $settings->saveSpam;

    try {
        $settings->spamKeywords = 'blocked-keyword';
        $settings->spamBehaviour = Settings::SPAM_BEHAVIOUR_SUCCESS;
        $settings->saveSpam = false;

        $submission = new Submission();
        $submission->setForm($form);
        $submission->setFieldValueFromRequest('message', 'contains blocked-keyword content');

        $response = runSubmissionCommand(submissionCommand([
            'operationId' => Craft::$app->getSecurity()->generateRandomString(),
            'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT,
            'form' => $form,
            'submission' => $submission,
            'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
        ]));

        expect($response->success)->toBeTrue()
            ->and($response->submission->isSpam)->toBeTrue()
            ->and($response->submission->id)->toBeNull()
            ->and($response->submission->getErrors('form'))->toBeEmpty();
    } finally {
        $settings->spamKeywords = $originalKeywords;
        $settings->spamBehaviour = $originalBehaviour;
        $settings->saveSpam = $originalSaveSpam;
    }
    });
});
