<?php

declare(strict_types=1);

use verbb\formie\Formie;

it('renders the required asterisk on the agree field option label when the label is hidden', function (): void {
    $form = formie()
        ->form(['title' => 'Agree Required Indicator'])
        ->settings(['requiredIndicator' => 'asterisk'])
        ->agreeField('terms', [
            'required' => true,
            'description' => '<p>I agree to the terms</p>',
        ])
        ->create();

    $html = (string)Formie::$plugin->getRendering()->renderField($form, 'terms');

    expect($html)
        ->toContain('data-formie-agree-option-label')
        ->toContain('data-formie-field-required')
        ->toContain('I agree to the terms')
        ->toMatch('/data-formie-agree-option-label[^>]*>[\s\S]*I agree to the terms[\s\S]*data-formie-field-required/');
});

it('does not duplicate the required asterisk when the agree field label is visible', function (): void {
    $form = formie()
        ->form(['title' => 'Agree Visible Label Required Indicator'])
        ->settings(['requiredIndicator' => 'asterisk'])
        ->agreeField('terms', [
            'required' => true,
            'label' => 'Consent',
            'labelPosition' => 'verbb\\formie\\positions\\AboveInput',
            'description' => '<p>I agree to the terms</p>',
        ])
        ->create();

    $html = (string)Formie::$plugin->getRendering()->renderField($form, 'terms');

    expect($html)
        ->toContain('data-formie-agree-field-label')
        ->toContain('data-formie-field-required')
        ->toContain('Consent')
        ->not->toMatch('/data-formie-agree-option-label[^>]*>[\s\S]*data-formie-field-required/');
});
