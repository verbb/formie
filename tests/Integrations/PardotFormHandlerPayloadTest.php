<?php

declare(strict_types=1);

use verbb\formie\integrations\crm\Pardot;

it('flattens pardot form-handler option fields to scalar semicolon values', function (): void {
    $form = formie()
        ->form(['title' => 'Pardot Options Payload'])
        ->dropdownField('industry', ['options' => [
            ['label' => 'Tech', 'value' => 'tech'],
            ['label' => 'Finance', 'value' => 'finance'],
        ]])
        ->checkboxesField('services', ['options' => [
            ['label' => 'A', 'value' => 'a'],
            ['label' => 'B', 'value' => 'b'],
        ]])
        ->create();

    $submission = formie()
        ->submission($form)
        ->with([
            'industry' => 'tech',
            'services' => ['a', 'b'],
        ])
        ->save();

    $integration = new Pardot([
        'name' => 'Pardot',
        'handle' => 'pardotPayload' . uniqid(),
    ]);

    $method = new ReflectionMethod(Pardot::class, 'generatePayloadValues');
    $method->setAccessible(true);
    $payload = $method->invoke($integration, $submission);

    expect($payload['industry'] ?? null)->toBe('tech')
        ->and($payload['services'] ?? null)->toBe('a;b')
        ->and(array_key_exists('industry.value', $payload))->toBeFalse()
        ->and(array_key_exists('services.0.value', $payload))->toBeFalse();
});
