<?php

declare(strict_types=1);

use verbb\formie\Formie;

it('exposes a single canonical progression store behavior', function (): void {
    $settings = Formie::$plugin->getSettings();

    expect(isset($settings->submissionStore))->toBeFalse();
});

it('keeps submit-state identity isolated by draft context', function (): void {
    $form = formie()
        ->form(['title' => 'State Context'])
        ->singleLineTextField('fullName')
        ->create();

    $form->setDraftContext('context-a');
    $identityA = $form->getSubmitStateIdentity();

    $form->setDraftContext('context-b');
    $identityB = $form->getSubmitStateIdentity();

    expect($identityA)->not->toBe($identityB);
});

it('keeps submit-state keys isolated by render instance', function (): void {
    $form = formie()
        ->form(['title' => 'Render Isolation'])
        ->singleLineTextField('fullName')
        ->create();

    $form->setRenderId('render-a');
    $keyA = $form->getSubmitStateKey();

    $form->setRenderId('render-b');
    $keyB = $form->getSubmitStateKey();

    expect($keyA)->not->toBe($keyB);
});

it('keeps request token generation callable and resettable', function (): void {
    $form = formie()
        ->form(['title' => 'Token Contract'])
        ->singleLineTextField('fullName')
        ->create();

    $tokenA = $form->getRequestToken();
    $form->setRequestToken(null);
    $tokenB = $form->getRequestToken();

    expect($tokenA)->not->toBeEmpty()
        ->and($tokenB)->not->toBeEmpty();
});

it('uses configured grant expiry and rejects altered credentials', function (): void {
    $settings = Formie::$plugin->getSettings();
    $old = $settings->saveResumeTokenTtlDays;
    $settings->saveResumeTokenTtlDays = 2;
    try {
        [$form, $submission] = continuitySubmission();
        $service = Formie::$plugin->getSubmissionGrants();
        $grant = $service->issue($submission, \verbb\formie\services\SubmissionGrants::CONTINUE);
        expect($grant->expiresAt - time())->toBeGreaterThanOrEqual(172799)->toBeLessThanOrEqual(172800)
            ->and($service->verify($grant->token, \verbb\formie\services\SubmissionGrants::CONTINUE, $form))->not->toBeNull()
            ->and($service->verify($grant->token . 'altered', \verbb\formie\services\SubmissionGrants::CONTINUE, $form))->toBeNull();
    } finally {
        $settings->saveResumeTokenTtlDays = $old;
    }
});
