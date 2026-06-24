<?php

declare(strict_types=1);

use craft\elements\User;

it('rejects email addresses already used by Craft users when uniqueUserEmail is enabled', function (): void {
    $seedUser = User::find()->status(null)->username('formie-seed-user')->one();
    expect($seedUser)->not->toBeNull();

    $form = formie()
        ->form(['title' => 'Unique User Email'])
        ->emailField('email', ['uniqueUserEmail' => true])
        ->create();

    $invalid = formie()
        ->submission($form)
        ->with(['email' => $seedUser->email])
        ->allowValidationFailure()
        ->save();

    $valid = formie()
        ->submission($form)
        ->with(['email' => 'unused-' . uniqid('', true) . '@example.test'])
        ->save();

    expect($invalid)->toHaveFieldError('email')
        ->and($valid->id)->not->toBeNull();
});

it('allows existing Craft user emails when uniqueUserEmail is disabled', function (): void {
    $seedUser = User::find()->status(null)->username('formie-seed-user')->one();
    expect($seedUser)->not->toBeNull();

    $form = formie()
        ->form(['title' => 'Unique User Email Disabled'])
        ->emailField('email')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['email' => $seedUser->email])
        ->save();

    expect($submission->id)->not->toBeNull();
});

it('registers uniqueUserEmail validation rules on the email field', function (): void {
    $form = formie()
        ->form(['title' => 'Unique User Email Rules'])
        ->emailField('email', ['uniqueUserEmail' => true])
        ->create();

    $field = $form->getFieldByHandle('email');
    $rules = $field?->getElementValidationRules() ?? [];

    expect($rules)->toContain(['email', 'validateUniqueUserEmail', 'skipOnEmpty' => true]);
});
