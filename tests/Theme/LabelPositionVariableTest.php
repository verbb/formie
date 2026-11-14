<?php

declare(strict_types=1);

use verbb\formie\positions\AboveInput;
use verbb\formie\positions\BelowInput;
use verbb\formie\positions\LeftInput;
use verbb\formie\variables\Formie as FormieVariable;

it('resolves the documented sub-field label position helper', function (): void {
    $form = formie()->form()
        ->settings(['defaultLabelPosition' => AboveInput::class])
        ->nameField('fullName', ['useMultipleFields' => true, 'labelPosition' => BelowInput::class, 'subFieldLabelPosition' => LeftInput::class])
        ->singleLineTextField('plain', ['labelPosition' => BelowInput::class])
        ->create();

    $variable = new FormieVariable();
    $name = $form->getFieldByHandle('fullName');
    $plain = $form->getFieldByHandle('plain');

    expect($name->subFieldLabelPosition)->toBe(LeftInput::class)
        ->and($variable->getLabelPosition($name, $form, true))->toBeInstanceOf(LeftInput::class)
        ->and($variable->getLabelPosition($name, $form))->toBeInstanceOf(BelowInput::class)
        ->and($variable->getLabelPosition($plain, $form, true))->toBeInstanceOf(BelowInput::class);
});
