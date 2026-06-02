<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\services\SubmissionDrafts;
use verbb\formie\state\DraftSubmissionState;

it('persists and restores draft state content via canonical submission store', function (): void {
    $form = formie()
        ->form(['title' => 'Storage Backend Matrix'])
        ->singleLineTextField('fullName')
        ->create();

    $submissionDrafts = new SubmissionDrafts();
    $key = $submissionDrafts->resolveFormInstanceKey($form, null, [
        'scope' => 'scope-canonical',
        'instance' => 'instance-canonical',
    ]);
    $state = new DraftSubmissionState([
        'formInstanceKey' => $key,
        'content' => ['field-uid' => 'value-canonical'],
        'snapshot' => ['store' => 'canonical'],
        'version' => 1,
    ]);

    $submissionDrafts->saveDraftState($state);
    $loaded = $submissionDrafts->loadDraftState($key);

    expect($loaded)->not->toBeNull()
        ->and($loaded?->content['field-uid'] ?? null)->toBe('value-canonical')
        ->and($loaded?->snapshot['store'] ?? null)->toBe('canonical');
});

it('isolates draft state by context scope and render instance to prevent bleed', function (): void {
    $form = formie()
        ->form(['title' => 'Context Render Isolation'])
        ->singleLineTextField('fullName')
        ->create();

    $submissionDrafts = Formie::$plugin->getSubmissionDrafts();

    $contextA = ['scope' => 'ctx-a', 'instance' => 'render-a'];
    $contextB = ['scope' => 'ctx-b', 'instance' => 'render-b'];

    $keyA = $submissionDrafts->resolveFormInstanceKey($form, null, $contextA);
    $keyB = $submissionDrafts->resolveFormInstanceKey($form, null, $contextB);

    $stateA = new DraftSubmissionState([
        'formInstanceKey' => $keyA,
        'content' => ['fieldA' => 'valueA'],
        'snapshot' => ['context' => 'A'],
        'version' => 1,
    ]);

    $submissionDrafts->saveDraftState($stateA);

    $loadedA = $submissionDrafts->loadDraftState($keyA);
    $loadedB = $submissionDrafts->loadDraftState($keyB);

    expect($loadedA)->not->toBeNull()
        ->and($loadedA?->snapshot['context'] ?? null)->toBe('A')
        ->and($loadedB)->toBeNull();
});

it('keeps request tokens generated per form instance without manager storage', function (): void {
    $form = formie()
        ->form(['title' => 'Request Token Isolation'])
        ->singleLineTextField('fullName')
        ->create();

    $tokenA = $form->getRequestToken();
    $form->resetRequestToken();
    $tokenB = $form->getRequestToken();

    expect($tokenA)->not->toBeEmpty()
        ->and($tokenB)->not->toBeEmpty()
        ->and($tokenA)->not->toBe($tokenB);
});
