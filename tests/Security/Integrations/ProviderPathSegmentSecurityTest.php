<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\helpers\StringHelper;
use verbb\formie\integrations\crm\Agile;
use verbb\formie\integrations\crm\Dotdigital;
use verbb\formie\integrations\crm\Pardot;
use verbb\formie\integrations\crm\Xero;
use verbb\formie\models\IntegrationResult;

class SecurityPardotPathProbe extends Pardot
{
    public array $endpoints = [];

    public function runForTest(Submission $submission): IntegrationResult
    {
        return $this->executePayload($submission);
    }

    public function getFieldMappingValues(Submission $submission, ?array $fieldMapping, mixed $fieldSettings = null)
    {
        return $fieldSettings === 'prospect' ? [
            'email' => 'person@example.test/../../campaign?next=/admin#fragment',
            'list_id' => '../private-list?all=1',
        ] : [];
    }

    public function request(string $method, string $uri, array $options = []): mixed
    {
        $this->endpoints[] = $uri;

        return [];
    }

    public function deliverPayload(Submission $submission, string $endpoint, mixed $payload, string $method = 'POST', string $contentType = 'json'): mixed
    {
        $this->endpoints[] = $endpoint;

        if (str_starts_with($endpoint, 'prospect/version/4/do/create/')) {
            return ['prospect' => ['id' => '../remote-id?all=1']];
        }

        return [];
    }
}

class SecurityAgilePathProbe extends Agile
{
    public array $endpoints = [];

    public function runForTest(Submission $submission): IntegrationResult
    {
        return $this->executePayload($submission);
    }

    public function getFieldMappingValues(Submission $submission, ?array $fieldMapping, mixed $fieldSettings = null)
    {
        return $fieldSettings === 'contact' ? [
            'email' => 'person@example.test/../../contacts?all=1#fragment',
        ] : [];
    }

    public function request(string $method, string $uri, array $options = []): mixed
    {
        $this->endpoints[] = $uri;

        return [];
    }

    public function deliverPayload(Submission $submission, string $endpoint, mixed $payload, string $method = 'POST', string $contentType = 'json'): mixed
    {
        return ['id' => 'contact-id'];
    }
}

class SecurityDotdigitalPathProbe extends Dotdigital
{
    public array $endpoints = [];

    public function runForTest(Submission $submission): IntegrationResult
    {
        return $this->executePayload($submission);
    }

    public function getFieldMappingValues(Submission $submission, ?array $fieldMapping, mixed $fieldSettings = null)
    {
        return $fieldSettings === 'contact' ? [
            'email' => 'person@example.test',
            'addressBook' => '../private-book?all=1#fragment',
        ] : [];
    }

    public function deliverPayload(Submission $submission, string $endpoint, mixed $payload, string $method = 'POST', string $contentType = 'json'): mixed
    {
        $this->endpoints[] = $endpoint;

        return $endpoint === 'contacts/with-consent-and-preferences'
            ? ['contact' => ['id' => 'contact-id']]
            : ['id' => 'contact-id'];
    }
}

class SecurityXeroPathProbe extends Xero
{
    public array $endpoints = [];

    public function runForTest(Submission $submission): IntegrationResult
    {
        return $this->executePayload($submission);
    }

    public function getFieldMappingValues(Submission $submission, ?array $fieldMapping, mixed $fieldSettings = null)
    {
        return [
            'Name' => 'Example',
            'ContactGroups' => '../private-group?all=1#fragment',
        ];
    }

    public function request(string $method, string $uri, array $options = []): mixed
    {
        $this->endpoints[] = $uri;

        return [];
    }

    public function deliverPayload(Submission $submission, string $endpoint, mixed $payload, string $method = 'POST', string $contentType = 'json'): mixed
    {
        return ['Contacts' => [['ContactID' => 'contact-id']]];
    }
}

it('encodes provider path segments without changing ordinary values', function (): void {
    expect(StringHelper::encodePathSegment('person+tag@example.test'))->toBe('person%2Btag%40example.test')
        ->and(StringHelper::encodePathSegment('12345'))->toBe('12345')
        ->and(StringHelper::encodePathSegment('014c912c-6836-4e58-9c55-725a80101e34'))->toBe('014c912c-6836-4e58-9c55-725a80101e34')
        ->and(StringHelper::encodePathSegment('.'))->toBe('%2E')
        ->and(StringHelper::encodePathSegment('..'))->toBe('%2E%2E')
        ->and(StringHelper::encodePathSegment('../../contacts?all=1#fragment'))->toBe('..%2F..%2Fcontacts%3Fall%3D1%23fragment');
})->group('security');

it('keeps mapped Pardot values within their provider path segments', function (): void {
    $integration = new SecurityPardotPathProbe([
        'name' => 'Pardot path probe',
        'handle' => 'pardotPathProbe',
        'mapToProspect' => true,
    ]);

    $integration->runForTest(new Submission());

    expect($integration->endpoints)->toBe([
        'prospect/version/4/do/read/email/person%40example.test%2F..%2F..%2Fcampaign%3Fnext%3D%2Fadmin%23fragment',
        'prospect/version/4/do/create/person%40example.test%2F..%2F..%2Fcampaign%3Fnext%3D%2Fadmin%23fragment',
        'listMembership/version/4/do/create/list_id/..%2Fprivate-list%3Fall%3D1/..%2Fremote-id%3Fall%3D1',
    ]);
})->group('security');

it('keeps mapped Agile emails within the lookup path segment', function (): void {
    $integration = new SecurityAgilePathProbe([
        'name' => 'Agile path probe',
        'handle' => 'agilePathProbe',
        'mapToContact' => true,
    ]);

    $integration->runForTest(new Submission());

    expect($integration->endpoints)->toBe([
        'contacts/search/email/person%40example.test%2F..%2F..%2Fcontacts%3Fall%3D1%23fragment',
    ]);
})->group('security');

it('keeps mapped Dotdigital address books within the provider path segment', function (): void {
    $integration = new SecurityDotdigitalPathProbe([
        'name' => 'Dotdigital path probe',
        'handle' => 'dotdigitalPathProbe',
        'mapToContact' => true,
    ]);

    $integration->runForTest(new Submission());

    expect($integration->endpoints)->toBe([
        'contacts/with-consent-and-preferences',
        'address-books/..%2Fprivate-book%3Fall%3D1%23fragment/contacts',
    ]);
})->group('security');

it('keeps mapped Xero contact groups within the provider path segment', function (): void {
    $integration = new SecurityXeroPathProbe([
        'name' => 'Xero path probe',
        'handle' => 'xeroPathProbe',
        'mapToContact' => true,
    ]);

    $integration->runForTest(new Submission());

    expect($integration->endpoints)->toBe([
        'api.xro/2.0/ContactGroups/..%2Fprivate-group%3Fall%3D1%23fragment/Contacts',
    ]);
})->group('security');
