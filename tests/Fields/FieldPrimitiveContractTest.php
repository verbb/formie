<?php

declare(strict_types=1);

it('saves submissions for primitive field families', function (): void {
    $form = formie()
        ->form(['title' => 'Primitive Fields'])
        ->singleLineTextField('fullName')
        ->emailField('email')
        ->numberField('age')
        ->dateField('dob')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with([
            'fullName' => 'Josh Crawford',
            'email' => 'josh@example.test',
            'age' => '32',
            'dob' => '1992-01-10',
        ])
        ->save();

    expect($submission->id)->not->toBeNull()
        ->and($submission->getFieldValue('email'))->not->toBeNull();
});

it('surfaces validation errors for invalid primitive values', function (): void {
    $form = formie()
        ->form(['title' => 'Primitive Validation'])
        ->emailField('email', ['required' => true])
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['email' => 'not-an-email'])
        ->allowValidationFailure()
        ->save();

    expect($submission)->toHaveFieldError('email');
});
