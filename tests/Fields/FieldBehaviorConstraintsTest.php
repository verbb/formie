<?php

declare(strict_types=1);

use verbb\formie\helpers\References;

it('keeps min-only max-only and min-max contracts for text limits', function (): void {
    $minForm = formie()
        ->form(['title' => 'Min Only'])
        ->singleLineTextField('value', [
            'limit' => true,
            'min' => 3,
            'minType' => 'characters',
        ])
        ->create();
    $minInvalid = formie()->submission($minForm)->with(['value' => 'ab'])->allowValidationFailure()->save();
    $minValid = formie()->submission($minForm)->with(['value' => 'abc'])->save();

    $maxForm = formie()
        ->form(['title' => 'Max Only'])
        ->singleLineTextField('value', [
            'limit' => true,
            'max' => 3,
            'maxType' => 'characters',
        ])
        ->create();
    $maxInvalid = formie()->submission($maxForm)->with(['value' => 'abcd'])->allowValidationFailure()->save();
    $maxValid = formie()->submission($maxForm)->with(['value' => 'abc'])->save();

    expect($minInvalid)->toHaveFieldError('value')
        ->and($minValid->id)->not->toBeNull()
        ->and($maxInvalid)->toHaveFieldError('value')
        ->and($maxValid->id)->not->toBeNull();
});

it('counts spaces toward character limits consistently', function (): void {
    $form = formie()
        ->form(['title' => 'Whitespace Character Limits'])
        ->singleLineTextField('value', [
            'limit' => true,
            'max' => 3,
            'maxType' => 'characters',
        ])
        ->create();

    $valid = formie()->submission($form)->with(['value' => '   '])->save();
    $invalid = formie()->submission($form)->with(['value' => '    '])->allowValidationFailure()->save();

    expect($valid->id)->not->toBeNull()
        ->and($invalid)->toHaveFieldError('value');
});

it('keeps match and unique contracts for both text and email families', function (): void {
    $matchForm = formie()
        ->form(['title' => 'Match Expanded'])
        ->emailField('email', ['required' => true])
        ->emailField('confirmEmail', ['required' => true])
        ->create();
    $emailField = $matchForm->getFieldByHandle('email');
    $confirmEmailField = $matchForm->getFieldByHandle('confirmEmail');
    $confirmEmailField->matchField = References::field((string)$emailField?->reference);

    expect(Craft::$app->elements->saveElement($matchForm))->toBeTrue();

    $matchInvalid = formie()->submission($matchForm)->with([
        'email' => 'one@example.test',
        'confirmEmail' => 'two@example.test',
    ])->allowValidationFailure()->save();
    $matchValid = formie()->submission($matchForm)->with([
        'email' => 'one@example.test',
        'confirmEmail' => 'one@example.test',
    ])->save();

    $uniqueForm = formie()
        ->form(['title' => 'Unique Expanded'])
        ->emailField('email', ['uniqueValue' => true])
        ->create();

    $first = formie()->submission($uniqueForm)->with(['email' => 'unique@example.test'])->save();
    $second = formie()->submission($uniqueForm)->with(['email' => 'unique@example.test'])->allowValidationFailure()->save();

    expect($matchInvalid)->toHaveFieldError('confirmEmail')
        ->and($matchValid->id)->not->toBeNull()
        ->and($first->id)->not->toBeNull()
        ->and($second)->toHaveFieldError('email');
});
