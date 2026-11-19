<?php

use verbb\formie\base\Integration;
use verbb\formie\helpers\References;
use verbb\formie\integrations\crm\Salesforce;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;

// Fields fetched from the provider, stored the way a refresh stores them.
function emptyFieldCacheStore(Integration $integration, array $config): void
{
    $integration->cache = ['config' => (new IntegrationConfig($config, $integration->getIntegrationConfigKey(), time()))->toStorage()];
}

function emptyFieldCacheSubmission(): array
{
    $form = formie()->form()->singleLineTextField('lastName')->emailField('emailAddress')->create();
    $submission = formie()->submission($form)->with(['lastName' => 'Crawford', 'emailAddress' => 'person@example.test'])->save();
    $reference = static fn(string $handle): string => References::field($form->getFieldByHandle($handle)->reference ?? $handle);

    return [$submission, $reference];
}

it('sends mapped CRM values when no provider fields are cached for the object', function(): void {
    [$submission, $reference] = emptyFieldCacheSubmission();
    $integration = new Salesforce(['name' => 'Empty cache', 'handle' => 'emptyCacheSalesforce']);
    $integration->cache = [];

    expect($integration->getFieldMappingValues($submission, ['LastName' => $reference('lastName'), 'Company' => 'Acme'], 'lead'))
        ->toBe(['LastName' => 'Crawford', 'Company' => 'Acme']);
});

it('keeps only cached CRM destinations once provider fields are cached', function(): void {
    [$submission, $reference] = emptyFieldCacheSubmission();
    $integration = new Salesforce(['name' => 'Cached fields', 'handle' => 'cachedFieldsSalesforce']);
    emptyFieldCacheStore($integration, ['lead' => [new IntegrationField(['handle' => 'LastName', 'name' => 'Last Name', 'type' => IntegrationField::TYPE_STRING])]]);

    expect($integration->getFieldMappingValues($submission, ['LastName' => $reference('lastName'), 'Removed__c' => 'Acme'], 'lead'))
        ->toBe(['LastName' => 'Crawford']);
});

it('sends mapped email marketing values when no list fields are cached', function(): void {
    [$submission, $reference] = emptyFieldCacheSubmission();
    $integration = new Mailchimp(['name' => 'Empty cache', 'handle' => 'emptyCacheMailchimp', 'listId' => 'list1']);
    $integration->cache = [];

    expect($integration->getFieldMappingValues($submission, ['email_address' => $reference('emailAddress'), 'LNAME' => $reference('lastName')]))
        ->toBe(['email_address' => 'person@example.test', 'LNAME' => 'Crawford']);
});

it('keeps only cached list destinations once list fields are cached', function(): void {
    [$submission, $reference] = emptyFieldCacheSubmission();
    $integration = new Mailchimp(['name' => 'Cached list', 'handle' => 'cachedListMailchimp', 'listId' => 'list1']);
    emptyFieldCacheStore($integration, ['lists' => [new IntegrationCollection([
        'id' => 'list1',
        'name' => 'List 1',
        'fields' => [new IntegrationField(['handle' => 'email_address', 'name' => 'Email', 'type' => IntegrationField::TYPE_STRING])],
    ])]]);

    expect($integration->getFieldMappingValues($submission, ['email_address' => $reference('emailAddress'), 'REMOVED' => $reference('lastName')]))
        ->toBe(['email_address' => 'person@example.test']);
});
