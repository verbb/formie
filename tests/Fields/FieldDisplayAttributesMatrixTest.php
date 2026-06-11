<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\fields\SingleLineText;

it('supports all label and instruction positions for single line text field', function (): void {
    $baseForm = formie()
        ->form(['title' => 'Position Matrix'])
        ->singleLineTextField('fullName')
        ->create();

    /** @var SingleLineText $baseField */
    $baseField = $baseForm->getFieldByHandle('fullName');

    $labelPositions = Formie::$plugin->getFields()->getLabelPositionsOptions($baseField);
    $instructionsPositions = Formie::$plugin->getFields()->getInstructionsPositionsOptions($baseField);

    foreach ($labelPositions as $labelOption) {
        foreach ($instructionsPositions as $instructionsOption) {
            $form = formie()
                ->form(['title' => 'Position ' . uniqid()])
                ->singleLineTextField('field', [
                    'labelPosition' => $labelOption['value'],
                    'instructionsPosition' => $instructionsOption['value'],
                    'instructions' => 'Instruction text',
                ])
                ->create();

            $field = $form->getFieldByHandle('field');

            expect($field?->labelPosition)->toBe($labelOption['value']);
            expect($field?->instructionsPosition)->toBe($instructionsOption['value']);
            expect($field?->hasInstructions())->toBeTrue();
        }
    }
});

it('supports css classes container attributes and input attributes contracts', function (): void {
    $form = formie()
        ->form(['title' => 'Attributes Matrix'])
        ->singleLineTextField('fullName', [
            'cssClasses' => 'custom-field-class another-class',
            'containerAttributes' => [
                ['label' => 'data-test-container', 'value' => 'container-value'],
            ],
            'inputAttributes' => [
                ['label' => 'data-test-input', 'value' => 'input-value'],
            ],
        ])
        ->create();

    $field = $form->getFieldByHandle('fullName');
    $containerAttrs = $field?->getContainerAttributes() ?? [];
    $inputAttrs = $field?->getInputAttributes() ?? [];

    expect($field?->cssClasses)->toContain('custom-field-class')
        ->and($containerAttrs)->toHaveKey('data-test-container')
        ->and($containerAttrs['data-test-container'])->toBe('container-value')
        ->and($inputAttrs)->toHaveKey('data-test-input')
        ->and($inputAttrs['data-test-input'])->toBe('input-value');
});

it('supports placeholder and default value contracts across primary field shortcuts', function (): void {
    $methods = [
        'singleLineTextField' => 'default-singleLineTextField',
        'multiLineTextField' => 'default-multiLineTextField',
        'emailField' => 'default@example.test',
        'numberField' => 123,
        'dateField' => '2025-01-02',
        'phoneField' => '+441234567890',
        'passwordField' => 'default-passwordField',
        'nameField' => 'Default Name',
    ];

    foreach ($methods as $method => $defaultValue) {
        $handle = strtolower($method) . 'Value';
        $form = formie()
            ->form(['title' => 'Defaults ' . $method . ' ' . uniqid()])
            ->{$method}($handle, [
                'placeholder' => 'placeholder-' . $method,
                'defaultValue' => $defaultValue,
            ])
            ->create();

        $field = $form->getFieldByHandle($handle);

        expect($field)->not->toBeNull();
        expect($field?->placeholder)->toBe('placeholder-' . $method);
    }
});

it('supports visible hidden and disabled visibility contracts across primary fields', function (): void {
    $visibilityStates = ['visible', 'hidden', 'disabled'];

    foreach ($visibilityStates as $visibility) {
        $form = formie()
            ->form(['title' => 'Visibility ' . $visibility])
            ->singleLineTextField('field', ['visibility' => $visibility])
            ->create();

        $field = $form->getFieldByHandle('field');

        expect($field?->visibility)->toBe($visibility);

        if ($visibility === 'hidden') {
            expect($field?->getIsHidden())->toBeTrue();
        }

        if ($visibility === 'disabled') {
            expect($field?->getIsDisabled())->toBeTrue();
        }
    }
});
