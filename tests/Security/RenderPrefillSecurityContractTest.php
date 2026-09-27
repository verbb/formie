<?php

declare(strict_types=1);

use Tests\Support\MaliciousPayloads;
use verbb\formie\Formie;

it('preserves populated values as literal data without executing Twig', function (): void {
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

    expect($initialValue)->toBe(MaliciousPayloads::twigProbe());
})->group('security');
