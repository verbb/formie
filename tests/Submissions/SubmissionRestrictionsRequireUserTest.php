<?php

declare(strict_types=1);

use craft\elements\User;
use verbb\formie\elements\Submission;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\services\SubmissionWorkflow;

it('blocks guest submissions when require-user is enabled and allows authenticated users', function (): void {
    $form = formie()
        ->form(['title' => 'Require User Restriction'])
        ->singleLineTextField('fullName', ['required' => true])
        ->settings(['disableCaptchas' => true])
        ->create();

    $form->settings->setAttributes([
        'requireUser' => true,
        'requireUserMessage' => 'Please sign in before submitting.',
    ], false);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    Craft::$app->getUser()->setIdentity(null);

    $guestSubmission = new Submission();
    $guestSubmission->setForm($form);
    $guestSubmission->setFieldValueFromRequest('fullName', 'Guest Attempt');

    $guestResponse = runSubmissionCommand(submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT,
        'form' => $form,
        'submission' => $guestSubmission,
        'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
    ]));

    expect($guestResponse->success)->toBeFalse()
        ->and(json_encode($guestResponse->submission->getErrors()))->toContain('logged in');

    $seedUser = User::find()->status(null)->username('formie-seed-user')->one();
    expect($seedUser)->not->toBeNull();
    Craft::$app->getUser()->setIdentity($seedUser);

    try {
        $authSubmission = new Submission();
        $authSubmission->setForm($form);
        $authSubmission->setFieldValueFromRequest('fullName', 'Authenticated User');

        $authResponse = runSubmissionCommand(submissionCommand([
            'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT,
            'form' => $form,
            'submission' => $authSubmission,
            'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
        ]));

        expect($authResponse->success)->toBeTrue()
            ->and($authResponse->submission->id)->not->toBeNull();
    } finally {
        Craft::$app->getUser()->setIdentity(null);
    }
});

it('exposes the configured require-user message when form is unavailable to guests', function (): void {
    $form = formie()
        ->form(['title' => 'Require User Message Contract'])
        ->singleLineTextField('fullName')
        ->settings(['disableCaptchas' => true])
        ->create();

    $form->settings->setAttributes([
        'requireUser' => true,
        'requireUserMessage' => 'LOGIN_REQUIRED_MESSAGE',
    ], false);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    Craft::$app->getUser()->setIdentity(null);

    expect($form->isAvailable())->toBeFalse()
        ->and(strip_tags($form->settings->getRequireUserMessage()))->toContain('LOGIN_REQUIRED_MESSAGE');
});
