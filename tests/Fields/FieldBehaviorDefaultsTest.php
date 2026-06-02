<?php

declare(strict_types=1);

dataset('field_behavior_default_cases', [
    ['singleLineTextField', 'string-default'],
    ['multiLineTextField', 'line one line two'],
    ['emailField', 'matrix@example.test'],
    ['numberField', 321],
    ['dateField', '2026-01-02'],
    ['phoneField', '+441234567890'],
    ['passwordField', 'secret-value'],
    ['nameField', 'Jane Matrix'],
    ['hiddenField', 'hidden-value'],
]);

it('persists placeholder and default value across applicable field shortcuts', function (string $method, mixed $defaultValue): void {
    $handle = strtolower($method) . 'Matrix';

    $form = formie()
        ->form(['title' => 'Field Behavior Defaults ' . $method . ' ' . uniqid()])
        ->{$method}($handle, [
            'placeholder' => 'placeholder-' . $method,
            'defaultValue' => $defaultValue,
        ])
        ->create();

    $field = $form->getFieldByHandle($handle);

    expect($field)->not->toBeNull()
        ->and($field?->placeholder)->toBe('placeholder-' . $method);
})->with('field_behavior_default_cases');
