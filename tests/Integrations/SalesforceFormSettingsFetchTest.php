<?php

declare(strict_types=1);

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use verbb\formie\integrations\crm\Salesforce;
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
