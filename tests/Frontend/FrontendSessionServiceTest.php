<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\client\models\PageTransitionRequest;
use verbb\formie\client\models\SessionRefreshRequest;

use yii\web\BadRequestHttpException;

it('builds draft-aware frontend sessions', function(): void {
    $form = formie()
        ->form(['title' => 'Frontend Session'])
        ->multiPage(2)
        ->onPage(1)
        ->singleLineTextField('firstName')
        ->onPage(2)
        ->singleLineTextField('lastName')
        ->create();

    $form->setDraftContext('custom:frontend-session');

    $session = WebRequestTestHelper::withWebRequestContext(function () use ($form): array {
        return Formie::$plugin->getClientSessionService()->issueInitialSession($form)->toArrayRecursive();
    }, [
        'method' => 'POST',
    ]);

    expect($session['currentPageId'])->toBe((string)$form->getPages()[0]->id)
        ->and($session['tokens']['request'] ?? null)->not->toBeEmpty()
        ->and($session['tokens']['render'] ?? null)->not->toBeEmpty()
        ->and($session['continuation']['draftContext'] ?? null)->toBe('custom:frontend-session')
        ->and($session['continuation']['draftContextToken'] ?? null)->not->toBeEmpty();
});

it('persists frontend page navigation through the session service', function(): void {
    $form = formie()
        ->form(['title' => 'Frontend Page Session'])
        ->multiPage(2)
        ->onPage(1)
        ->singleLineTextField('firstName')
        ->onPage(2)
        ->singleLineTextField('lastName')
        ->create();

    $pages = $form->getPages();

    $session = WebRequestTestHelper::withWebRequestContext(function () use ($form, $pages): array {
        return Formie::$plugin->getClientSessionService()->persistPageState(new PageTransitionRequest([
            'handle' => $form->handle,
            'targetPageId' => (string)$pages[1]->id,
            'session' => [],
            'values' => [],
        ]))->toArrayRecursive();
    }, [
        'method' => 'POST',
    ]);

    expect($session['currentPageId'])->toBe((string)$pages[1]->id);
});

it('issues opaque runtime continuation tokens instead of exposing submission uids in session payloads', function(): void {
    $form = formie()
        ->form(['title' => 'Frontend Continuation Token'])
        ->singleLineTextField('firstName')
        ->create();
    $submission = formie()
        ->submission($form)
        ->with(['firstName' => 'Security Tester'])
        ->save();

    $submission->isIncomplete = true;
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
    Formie::$plugin->getSubmissionDrafts()->upsertProgressState($form, $submission, $form->getCurrentPage()?->id);

    $session = WebRequestTestHelper::withWebRequestContext(function () use ($form): array {
        return Formie::$plugin->getClientSessionService()->issueInitialSession($form)->toArrayRecursive();
    }, [
        'method' => 'POST',
    ]);

    expect($session['continuation']['continuationToken'] ?? null)->toBeString()
        ->and($session['continuation']['continuationToken'] ?? '')->not->toContain((string)$submission->uid)
        ->and($session['continuation']['submissionUid'] ?? null)->toBeNull();
});

it('fails cleanly when refreshing an unknown frontend session form', function(): void {
    expect(fn() => Formie::$plugin->getClientSessionService()->refreshSession(new SessionRefreshRequest([
        'handle' => 'missing-form',
        'session' => [],
    ])))->toThrow(BadRequestHttpException::class);
});
