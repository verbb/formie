<?php

declare(strict_types=1);

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use verbb\formie\integrations\crm\Salesforce;
use verbb\formie\models\FormIntegration;
use verbb\formie\models\IntegrationSettingsContext;

it('scopes Salesforce form settings refresh to the requested data key', function (): void {
    $integration = new Salesforce([
        'name' => 'Salesforce',
        'handle' => 'salesforce',
        'mapToContact' => true,
        'mapToLead' => true,
        'mapToOpportunity' => true,
        'settingsContext' => new IntegrationSettingsContext(['dataKey' => 'lead']),
    ]);

    $method = new ReflectionMethod($integration, '_shouldFetchSettingsKey');
    $method->setAccessible(true);

    expect($method->invoke($integration, 'lead', true))->toBeTrue()
        ->and($method->invoke($integration, 'contact', true))->toBeFalse()
        ->and($method->invoke($integration, 'opportunity', true))->toBeFalse()
        ->and($method->invoke($integration, 'lead', false))->toBeFalse();
});

it('fetches all enabled Salesforce objects when refresh has no data key', function (): void {
    $integration = new Salesforce([
        'name' => 'Salesforce',
        'handle' => 'salesforce',
        'mapToContact' => true,
        'mapToLead' => true,
        'settingsContext' => new IntegrationSettingsContext(),
    ]);

    $method = new ReflectionMethod($integration, '_shouldFetchSettingsKey');
    $method->setAccessible(true);

    expect($method->invoke($integration, 'contact', true))->toBeTrue()
        ->and($method->invoke($integration, 'lead', true))->toBeTrue()
        ->and($method->invoke($integration, 'opportunity', false))->toBeFalse();
});

it('detects Salesforce NOT_FOUND API errors', function (): void {
    $integration = new Salesforce([
        'name' => 'Salesforce',
        'handle' => 'salesforce',
    ]);

    $method = new ReflectionMethod($integration, '_isSalesforceNotFoundError');
    $method->setAccessible(true);

    $notFound = new RequestException(
        'Not Found',
        new Request('GET', 'test'),
        new Response(404, [], '[{"errorCode":"NOT_FOUND","message":"The requested resource does not exist"}]'),
    );

    $other = new RequestException(
        'Server Error',
        new Request('GET', 'test'),
        new Response(500, [], 'Internal Server Error'),
    );

    expect($method->invoke($integration, $notFound))->toBeTrue()
        ->and($method->invoke($integration, $other))->toBeFalse();
});

it('refreshes Salesforce Lead fields before delivery is enabled and reports empty field results', function (bool $updateable, int $expectedCount): void {
    $connection = new class(['name' => 'Salesforce', 'handle' => 'salesforce']) extends Salesforce {
        public array $requests = [];
        public bool $fieldUpdateable = true;

        public static function supportsConnection(): bool { return false; }

        public function request(string $method, string $uri, array $options = []): mixed
        {
            $this->requests[] = [$method, $uri];

            if ($uri === 'query') {
                return ['records' => []];
            }

            if ($uri !== 'sobjects/Lead/describe') {
                throw new RuntimeException('Unexpected Salesforce request: ' . $uri);
            }

            return ['fields' => [[
                'name' => 'LastName',
                'label' => 'Last Name',
                'type' => 'string',
                'updateable' => $this->fieldUpdateable,
                'createable' => $this->fieldUpdateable,
                'nillable' => false,
            ]]];
        }
    };
    $connection->fieldUpdateable = $updateable;
    $integration = FormIntegration::fromSettings($connection, [
        'enabled' => false,
        'mapToLead' => true,
        'mapToContact' => true,
    ])->createRuntime();
    $integration->settingsContext = new IntegrationSettingsContext(['dataKey' => 'lead']);
    $messageOffset = count(Craft::getLogger()->messages);
    $config = $integration->refreshConfig();
    $schema = $integration->createFieldMappingField(['dataKey' => 'lead', 'dataLabel' => 'Lead']);

    expect($integration->getEnabled())->toBeFalse()
        ->and($config->get('lead'))->toHaveCount($expectedCount)
        ->and($schema['integrationFields'])->toHaveCount($expectedCount)
        ->and($integration->requests)->toBe([['GET', 'query'], ['GET', 'sobjects/Lead/describe']]);

    $messages = array_column(array_slice(Craft::getLogger()->messages, $messageOffset), 0);
    expect(implode("\n", array_filter($messages, 'is_string')))
        ->toContain('Salesforce: Fetching Salesforce Lead fields.')
        ->toContain("Salesforce: Lead field refresh: received 1 fields, {$expectedCount} updateable, {$expectedCount} createable, {$expectedCount} available for mapping.")
        ->not->toContain('LastName');
})->with(['writable' => [true, 1], 'read-only' => [false, 0]]);
