<?php

use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\crm\Salesforce;
use verbb\formie\services\Integrations;

// The fields a Formie 3 site had fetched from Salesforce, stored the way Formie 3 stored them.
function configCacheOnSaveLegacyCache(): array
{
    $field = static fn(string $handle, string $name): array => ['class' => 'verbb\\formie\\models\\IntegrationField', 'handle' => $handle, 'name' => $name, 'type' => 'string', 'required' => '1', 'options' => [], 'data' => []];

    return ['settings' => ['lead' => [$field('LastName', 'Last Name'), $field('Company', 'Company')]]];
}

function configCacheOnSaveIntegration(string $scope): Salesforce
{
    $integration = new Salesforce([
        'name' => 'Cache on save',
        'handle' => 'cacheOnSave' . StringHelper::randomString(6),
        'scope' => $scope,
        'clientId' => 'client',
        'clientSecret' => 'secret',
        'apiDomain' => 'https://example.my.salesforce.com',
    ]);

    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();

    if ($scope === Integrations::SCOPE_PROJECT) {
        // An upgraded site's project config still has Formie 3's shape, so the first save after upgrading changes it.
        $path = Integrations::CONFIG_INTEGRATIONS_KEY . '.' . $integration->uid;
        $legacy = Craft::$app->getProjectConfig()->get($path);
        $legacy['enabled'] = '1';
        unset($legacy['settings']['grant'], $legacy['settings']['authDomain'], $legacy['settings']['mapToCampaignMember']);
        Craft::$app->getProjectConfig()->set($path, $legacy);
    }

    Db::update(Table::FORMIE_INTEGRATIONS, ['cache' => Json::encode(configCacheOnSaveLegacyCache())], ['id' => $integration->id]);

    return $integration;
}

// Delivery uses the cached fields as the allowlist for mapped values, so these are the fields a submission can send.
function configCacheOnSaveLeadFields(Salesforce $integration): array
{
    $stored = (new Query())->select('cache')->from(Table::FORMIE_INTEGRATIONS)->where(['id' => $integration->id])->scalar();
    $reloaded = clone $integration;
    $reloaded->cache = Json::decodeIfJson((string)$stored) ?: [];

    return array_map(static fn($field) => $field->handle, $reloaded->getConfigValue('lead') ?: []);
}

it('keeps fetched provider fields when an integration is saved without connection changes', function(string $scope): void {
    $integration = configCacheOnSaveIntegration($scope);
    $integration->name = 'Cache on save renamed';

    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue()
        ->and(configCacheOnSaveLeadFields($integration))->toBe(['LastName', 'Company']);
})->with([Integrations::SCOPE_PROJECT, Integrations::SCOPE_SITE]);

it('clears fetched provider fields when an existing connection setting changes', function(string $scope): void {
    $integration = configCacheOnSaveIntegration($scope);
    $integration->apiDomain = 'https://other.my.salesforce.com';

    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue()
        ->and(configCacheOnSaveLeadFields($integration))->toBe([]);
})->with([Integrations::SCOPE_PROJECT, Integrations::SCOPE_SITE]);
