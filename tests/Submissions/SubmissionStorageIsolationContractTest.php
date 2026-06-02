<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\services\SubmissionDrafts;
use verbb\formie\state\DraftSubmissionState;

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

it('uses configured resume token ttl days when issuing save tokens', function (): void {
    $settings = Formie::$plugin->getSettings();
    $originalTtlDays = $settings->saveResumeTokenTtlDays;
    $settings->saveResumeTokenTtlDays = 2;

    $form = formie()
        ->form(['title' => 'Resume TTL'])
        ->singleLineTextField('fullName')
        ->create();

    try {
        $submissionDrafts = new SubmissionDrafts();
        $key = $submissionDrafts->resolveFormInstanceKey($form, null, [
            'scope' => 'resume-ttl',
            'instance' => 'test',
        ]);

        $state = new DraftSubmissionState([
            'formInstanceKey' => $key,
            'content' => [],
            'snapshot' => [],
            'version' => 1,
        ]);

        $submissionDrafts->saveDraftState($state);
        $token = $submissionDrafts->issueResumeToken($state);

        expect($token->expiresAt)->not->toBeNull()
            ->and($token->issuedAt)->not->toBeNull()
            ->and(($token->expiresAt - $token->issuedAt))->toBeGreaterThanOrEqual(172799)
            ->and(($token->expiresAt - $token->issuedAt))->toBeLessThanOrEqual(172801);
    } finally {
        $settings->saveResumeTokenTtlDays = $originalTtlDays;
    }
});

it('issues and validates resume tokens for payload-first draft state', function (): void {
    $form = formie()
        ->form(['title' => 'Session Draft Limit'])
        ->singleLineTextField('fullName')
        ->create();

    $submissionDrafts = new SubmissionDrafts();
    $key = $submissionDrafts->resolveFormInstanceKey($form, null, [
        'scope' => 'state-token',
        'instance' => 'validation',
    ]);
    $state = new DraftSubmissionState([
        'formInstanceKey' => $key,
        'content' => ['fullName' => 'Token User'],
        'snapshot' => [],
        'version' => 1,
    ]);

    $submissionDrafts->saveDraftState($state);
    $token = $submissionDrafts->issueResumeToken($state);
    $verified = $submissionDrafts->verifyResumeToken($token->token, [SubmissionDrafts::RESUME_CAPABILITY_READ]);

    expect($token->token)->not->toBeEmpty()
        ->and($verified)->not->toBeNull()
        ->and((int)($verified?->formId ?? 0))->toBe((int)$form->id);
});

it('rejects tampered payload-first resume tokens', function (): void {
    $form = formie()
        ->form(['title' => 'State Token Tamper'])
        ->singleLineTextField('fullName')
        ->create();

    $submissionDrafts = new SubmissionDrafts();
    $key = $submissionDrafts->resolveFormInstanceKey($form, null, [
        'scope' => 'state-token',
        'instance' => 'tamper',
    ]);
    $state = new DraftSubmissionState([
        'formInstanceKey' => $key,
        'content' => ['fullName' => 'Token User'],
        'snapshot' => [],
        'version' => 1,
    ]);
    $submissionDrafts->saveDraftState($state);
    $token = $submissionDrafts->issueResumeToken($state);
    $tamperedToken = substr($token->token, 0, -2) . 'ab';

    expect($submissionDrafts->verifyResumeToken($tamperedToken, [SubmissionDrafts::RESUME_CAPABILITY_READ]))->toBeNull();
});
