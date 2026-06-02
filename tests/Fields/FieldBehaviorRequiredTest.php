<?php

declare(strict_types=1);

use Tests\Support\FieldCapabilityMatrix;

dataset('field_behavior_required_cases', FieldCapabilityMatrix::requiredFlagMethods());

it('persists required field contract across core input families', function (
    string $method,
    string $handle,
    array $fieldConfig
): void {
    $form = formie()
        ->form(['title' => 'Required Flag Matrix ' . $method . ' ' . uniqid()])
        ->{$method}($handle, array_merge($fieldConfig, ['required' => true]))
        ->create();

    $field = $form->getFieldByHandle($handle);

    expect((bool)($field?->required ?? false))->toBeTrue();
})->with('field_behavior_required_cases');

it('enforces required error state for a strict validation field contract', function (): void {
    $form = formie()
        ->form(['title' => 'Required Error Contract'])
        ->emailField('email', ['required' => true])
        ->create();

    $invalid = formie()->submission($form)->with(['email' => 'not-an-email'])->allowValidationFailure()->save();
    $valid = formie()->submission($form)->with(['email' => 'required@example.test'])->save();

    expect($invalid)->toHaveFieldError('email')
        ->and($valid->id)->not->toBeNull();
});
