<?php

declare(strict_types=1);

use verbb\formie\elements\Form;

it('validates required fields through submission interop with form context', function (): void {
    $form = formie()
        ->form(['title' => 'Submission Interop'])
        ->emailField('email', ['required' => true])
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['email' => 'not-an-email'])
        ->allowValidationFailure()
        ->save();

    expect($submission)->toHaveFieldError('email');
});

it('keeps submission compatibility after form updates', function (): void {
    $form = formie()
        ->form(['title' => 'Interop Update'])
        ->singleLineTextField('fullName')
        ->create();

    $first = formie()->submission($form)->with(['fullName' => 'Before'])->save();

    $form->title = 'Interop Update v2';
    Craft::$app->elements->saveElement($form);

    $reloadedForm = Form::find()->id($form->id)->one();
    $second = formie()->submission($reloadedForm)->with(['fullName' => 'After'])->save();

    expect($first->id)->not->toBeNull()
        ->and($second->id)->not->toBeNull()
        ->and($second->getForm()?->title)->toBe('Interop Update v2');
});
