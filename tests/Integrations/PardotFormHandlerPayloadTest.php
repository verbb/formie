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
        ->and(array_key_exists('industry.value', $payload))->toBeFalse()
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
        ->and($bodyParts)->toContain('industry=tech', 'services=a', 'services=b')
        ->and(array_values(array_filter($bodyParts, fn(string $part): bool => str_starts_with($part, 'services='))))->toHaveCount(2);
});
