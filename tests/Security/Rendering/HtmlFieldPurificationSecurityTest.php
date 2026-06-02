<?php

declare(strict_types=1);

use Tests\Support\MaliciousPayloads;

it('purifies rendered HTML field content before it reaches frontend sinks', function (): void {
    $form = formie()
        ->form(['title' => 'HTML Field Purification Contract'])
        ->htmlField('content', [
            'htmlContent' => MaliciousPayloads::storedXssProbe(),
            'purifyContent' => true,
        ])
        ->create();

    $field = $form->getFieldByHandle('content');
    $html = $field?->getRenderedHtmlBlock($form, null, null);

    expect($html)->toBeString()
        ->toContain('safe-text')
        ->not->toContain('<script')
        ->not->toContain('onerror=');
})->group('security');
