<?php

declare(strict_types=1);

use verbb\formie\integrations\crm\Pardot;
use verbb\formie\models\IntegrationResult;

class PardotFormHandlerDeliveryFixture extends Pardot
{
    public array $requests = [];

    public function requestPublicEndpoint(string $method, string $uri, array $options = []): mixed
    {
        $this->requests[] = compact('method', 'uri', 'options');

        return 'success';
    }

    public function executeForTest(\verbb\formie\elements\Submission $submission): IntegrationResult
    {
        return $this->executePayload($submission);
    }
}

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
        ->and($payload['industry.value'] ?? null)->toBe('tech')
        ->and(array_key_exists('services.0.value', $payload))->toBeFalse();
});

it('sends Pardot multi-option values as repeated form-handler parameters', function (): void {
    $form = formie()
        ->form(['title' => 'Pardot Repeated Parameters'])
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

    $integration = new PardotFormHandlerDeliveryFixture([
        'name' => 'Pardot',
        'handle' => 'pardotDelivery' . uniqid(),
        'enableFormHandler' => true,
        'endpointUrl' => 'https://example.com/pardot-handler',
    ]);

    $integration->executeForTest($submission);

    $request = $integration->requests[0] ?? [];
    $bodyParts = explode('&', (string)($request['options']['body'] ?? ''));

    expect($integration->requests)->toHaveCount(1)
        ->and($request['method'] ?? null)->toBe('POST')
        ->and($request['options']['headers']['Content-Type'] ?? null)->toBe('application/x-www-form-urlencoded')
        ->and($request['options'])->not->toHaveKey('form_params')
        ->and($bodyParts)->toContain('industry=tech', 'industry.value=tech', 'services=a', 'services=b')
        ->and(array_values(array_filter($bodyParts, fn(string $part): bool => str_starts_with($part, 'services='))))->toHaveCount(2);
});

it('preserves legacy Pardot country mappings without overriding payload listeners', function (string $mode): void {
    $form = formie()->form(['title' => 'Pardot Country Compatibility'])
        ->dropdownField('country', ['options' => [
            ['label' => 'Iraq', 'value' => 'IQ'],
        ]])
        ->radioField('category', ['options' => [
            ['label' => 'Zero', 'value' => '0'],
        ]])
        ->create();

    $submission = formie()->submission($form)->with(['country' => 'IQ', 'category' => '0'])->save();
    $integration = new PardotFormHandlerDeliveryFixture([
        'name' => 'Pardot',
        'handle' => 'pardotLegacy' . uniqid(),
        'enableFormHandler' => true,
        'endpointUrl' => 'https://example.com/pardot-handler',
    ]);

    $integration->on(Pardot::EVENT_MODIFY_FORM_HANDLER_PAYLOAD, function ($event) use ($mode): void {
        expect($event->payload['country'] ?? null)->toBe('IQ')
            ->and($event->payload['country.value'] ?? null)->toBe('IQ');

        if ($mode === 'override') {
            $event->payload['country.value'] = 'Custom & value';
        } elseif ($mode === 'remove') {
            unset($event->payload['country'], $event->payload['country.value']);
        }
    });

    $integration->executeForTest($submission);
    $parts = explode('&', $integration->requests[0]['options']['body']);
    expect($parts)->toContain('category=0', 'category.value=0');

    if ($mode === 'remove') {
        expect(array_filter($parts, fn(string $part): bool => str_starts_with($part, 'country')))->toBe([]);
    } else {
        expect($parts)->toContain('country=IQ', $mode === 'override' ? 'country.value=Custom+%26+value' : 'country.value=IQ');
    }
})->with(['unchanged', 'override', 'remove']);
