<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('persists incomplete and spam flags as submission state', function (): void {
    $form = formie()
        ->form(['title' => 'Submission Flags'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Flagged'])
        ->save();

    $submission->isIncomplete = true;
    $submission->isSpam = true;
    $saved = \Craft::$app->elements->saveElement($submission);

    $reloaded = Submission::find()->id($submission->id)->anyStatus()->one();

    expect($saved)->toBeTrue()
        ->and($reloaded?->isIncomplete)->toBeTrue()
        ->and($reloaded?->isSpam)->toBeTrue()
        ->and($reloaded?->getIsDraft())->toBeTrue();
});

it('persists submission snapshot contract payloads', function (): void {
    $form = formie()
        ->form(['title' => 'Snapshot State'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()->submission($form)->with(['fullName' => 'Snapshot'])->save();
    $submission->snapshot = [
        'form' => ['submitAction' => 'message'],
        'fields' => ['fullName' => ['required' => true]],
    ];

    $saved = \Craft::$app->elements->saveElement($submission);
    $reloaded = Submission::find()->id($submission->id)->one();

    expect($saved)->toBeTrue()
        ->and($reloaded?->snapshot)->toHaveKey('form')
        ->and($reloaded?->snapshot)->toHaveKey('fields');
});
