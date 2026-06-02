<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('saves valid submissions and reports invalid submissions', function (): void {
    $form = formie()
        ->form(['title' => 'Submission Lifecycle'])
        ->emailField('email', ['required' => true])
        ->create();

    $valid = formie()
        ->submission($form)
        ->with(['email' => 'valid@example.test'])
        ->save();

    $invalid = formie()
        ->submission($form)
        ->with(['email' => 'invalid-email'])
        ->allowValidationFailure()
        ->save();

    expect($valid->id)->not->toBeNull()
        ->and($invalid->id)->toBeNull()
        ->and($invalid)->toHaveFieldError('email');
});

it('persists and resolves submission status contracts', function (): void {
    $form = formie()
        ->form(['title' => 'Submission Status'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Status Value'])
        ->save();

    $statusHandle = $submission->getStatus();
    $reloaded = Submission::find()->id($submission->id)->one();

    expect($statusHandle)->not->toBeNull()
        ->and($reloaded?->getStatus())->toBe($statusHandle);
});
