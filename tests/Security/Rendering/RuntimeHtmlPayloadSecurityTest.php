<?php

declare(strict_types=1);

use Tests\Support\MaliciousPayloads;
use verbb\formie\Formie;

it('keeps populated hidden field values literal after runtime prefill normalization', function (): void {
    $form = formie()
        ->form(['title' => 'Runtime Prefill Security'])
        ->hiddenField('trackingToken', [
            'defaultOption' => 'custom',
            'defaultValue' => 'safe-default',
        ])
        ->create();

    Formie::$plugin->getRendering()->populateFormValues($form, [
        'trackingToken' => MaliciousPayloads::twigProbe(),
    ]);

    $field = $form->getFieldByHandle('trackingToken');
    $inputOptions = $field?->getInputTemplateVariables($form, $field?->getElementValue($form));
    $value = (string)($inputOptions['value'] ?? '');

    expect($value)
        ->toContain('TWIG_SENTINEL')
        ->toContain('CONTROL_SENTINEL')
        ->not->toContain('{{')
        ->not->toContain('}}')
        ->not->toContain('{%')
        ->not->toContain('%}');
})->group('security');
