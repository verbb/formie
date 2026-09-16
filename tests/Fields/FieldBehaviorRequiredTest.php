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

it('rejects an empty required email without relying on email format validation', function (array $values): void {
    $form = formie()
        ->form(['title' => 'Required Error Contract'])
        ->emailField('email', ['required' => true])
        ->create();

    $invalid = new \verbb\formie\elements\Submission();
    $invalid->setForm($form);
    $invalid->title = 'Required invalid';
    $invalid->setScenario(\craft\base\Element::SCENARIO_LIVE);
    foreach ($values as $handle => $value) {
        $invalid->setFieldValueFromRequest($handle, $value);
    }
    expect(Craft::$app->getElements()->saveElement($invalid))->toBeFalse();
    $valid = new \verbb\formie\elements\Submission();
    $valid->setForm($form);
    $valid->title = 'Required valid';
    $valid->setScenario(\craft\base\Element::SCENARIO_LIVE);
    $valid->setFieldValueFromRequest('email', 'required@example.test');
    expect(Craft::$app->getElements()->saveElement($valid))->toBeTrue();

    expect($invalid)->toHaveFieldError('email')
        ->and($invalid->id)->toBeNull()
        ->and($valid->id)->not->toBeNull();
})->with(['omitted' => [[]], 'empty' => [['email' => '']], 'null' => [['email' => null]]]);
