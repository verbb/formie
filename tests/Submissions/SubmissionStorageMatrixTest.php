<?php

declare(strict_types=1);

use verbb\formie\Formie;

it('persists provisional content in the database and isolates browser contexts', function (): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $service = Formie::$plugin->getSubmissionProgress();
    $form->setDraftContext('context-a');
    $state = $service->upsertPageState($form);
    $state->content = ['fullName' => 'provisional'];
    $service->saveProgress($state);
    expect($service->getProgressState($form)->content)->toBe(['fullName' => 'provisional']);
    $form->setDraftContext('context-b');
    expect($service->getProgressState($form))->toBeNull();
    $form->setDraftContext('context-a');
    expect($service->getProgressState($form)->id)->toBe($state->id);
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
