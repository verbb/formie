<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('surfaces invalid payload errors for strict primitive fields', function (): void {
    $form = formie()
        ->form(['title' => 'Negative Payload Matrix'])
        ->emailField('email', ['required' => true])
        ->numberField('age')
        ->create();

    $invalidEmail = formie()->submission($form)->with(['email' => 'invalid-email'])->allowValidationFailure()->save();
    $invalidNumber = formie()->submission($form)->with([
        'email' => 'ok@example.test',
        'age' => 'not-a-number',
    ])->allowValidationFailure()->save();

    expect($invalidEmail)->toHaveFieldError('email')
        ->and($invalidNumber)->toHaveFieldError('age');
});

it('keeps unknown query handles and impossible values non-fatal', function (): void {
    $form = formie()
        ->form(['title' => 'Negative Query Matrix'])
        ->singleLineTextField('fullName')
        ->create();

    formie()->submission($form)->with(['fullName' => 'Known Value'])->save();
    formie()->submission($form)->with(['fullName' => 'Second Value'])->save();

    $unknownHandleResults = Submission::find()
        ->formId($form->id)
        ->field('doesNotExist', 'anything')
        ->all();

    $impossibleValueResults = Submission::find()
        ->formId($form->id)
        ->field('fullName', 'Impossible Value')
        ->all();

    $allResults = Submission::find()
        ->formId($form->id)
        ->all();

    expect($unknownHandleResults)->toBeArray()
        ->and($unknownHandleResults)->toHaveCount(count($allResults))
        ->and($impossibleValueResults)->toHaveCount(0);
});
