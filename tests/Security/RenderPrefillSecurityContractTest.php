<?php

declare(strict_types=1);

use Tests\Support\MaliciousPayloads;
use verbb\formie\Formie;

it('strips Twig delimiters from populated values before render-time field population', function (): void {
    $form = formie()
        ->form(['title' => 'Render Prefill Security Contract'])
        ->hiddenField('trackingToken', [
            'defaultOption' => 'custom',
            'defaultValue' => 'safe-default',
        ])
        ->create();

    Formie::$plugin->getRendering()->populateFormValues($form, [
        'trackingToken' => MaliciousPayloads::twigProbe(),
    ]);

    $field = $form->getFieldByHandle('trackingToken');
    $initialValue = (string)$field?->getInitialValue($form);

    expect($initialValue)
        ->not->toContain('{{')
        ->not->toContain('}}')
        ->not->toContain('{%')
        ->not->toContain('%}')
        ->toContain('TWIG_SENTINEL')
        ->toContain('CONTROL_SENTINEL');
})->group('security');
