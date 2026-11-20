<?php

declare(strict_types=1);

use verbb\formie\positions\AboveInput;
use verbb\formie\positions\BelowInput;
use verbb\formie\positions\LeftInput;

// Client-rendered forms place a field's label, instructions and errors as its positions say, as the server template does.
it('sends the label, instructions and error positions to client-rendered forms', function(): void {
    $form = formie()->form()
        ->singleLineTextField('placed', ['label' => 'Placed', 'labelPosition' => LeftInput::class, 'instructionsPosition' => BelowInput::class, 'errorMessagePosition' => AboveInput::class])
        ->singleLineTextField('plain', ['label' => 'Plain'])
        ->create();

    try {
        $placed = $form->getFieldByHandle('placed')->getClientRenderedInput();
        $plain = $form->getFieldByHandle('plain')->getClientRenderedInput();

        expect([$placed['labelPosition'], $placed['instructionsPosition'], $placed['errorMessagePosition']])->toBe(['left', 'below', 'above'])
            ->and([$plain['labelPosition'], $plain['instructionsPosition'], $plain['errorMessagePosition']])->toBe(['above', 'above', 'below']);
    } finally {
        Craft::$app->getElements()->deleteElement($form, true);
    }
});
