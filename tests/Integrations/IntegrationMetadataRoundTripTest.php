<?php

use craft\db\Query;
use verbb\formie\base\Integration;
use verbb\formie\helpers\Table;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

class MetadataRoundTripFixture extends Integration
{
    public static function displayName(): string { return 'Metadata round-trip'; }
    public static function supportsConnection(): bool { return false; }
    public function fetchConfig(): IntegrationConfig
    {
        return new IntegrationConfig(['fields' => [new IntegrationField([
            'handle' => 'field10',
            'name' => 'Field 10',
            'options' => [['label' => 'Option 0', 'value' => '0'], ['label' => 'Option 1', 'value' => '1']],
        ])], 'label' => 'field10', 'url' => 'https://example.com/field10']);
    }
}

it('preserves metadata through refresh database storage and editor mapping despite matching credential text', function (string $secret): void {
    $integration = new MetadataRoundTripFixture(['name' => 'Round trip', 'handle' => 'metadataRoundTrip', 'clientSecret' => $secret]);
    expect(\verbb\formie\Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $expected = $integration->fetchConfig()->all();
    $config = $integration->refreshConfig();
    $stored = json_decode((new Query())->select('cache')->from(Table::FORMIE_INTEGRATIONS)->where(['id' => $integration->id])->scalar(), true);
    $reloaded = clone $integration;
    $reloaded->cache = $stored;
    $cached = $reloaded->getConfig();
    $schema = $reloaded->createFieldMappingField(['dataKey' => 'fields', 'dataLabel' => 'Fields']);

    expect($config->all())->toEqual($expected)
        ->and($cached->all())->toEqual($expected)
        ->and($cached->invalidationKey)->toBe($integration->getIntegrationConfigKey())
        ->and($cached->fetchedAt)->toBe($config->fetchedAt)
        ->and($cached->isStale())->toBeFalse()
        ->and($schema['integrationFields'][0]['handle'])->toBe('field10')
        ->and($schema['integrationFields'][0]['name'])->toBe('Field 10')
        ->and($cached->get('fields')[0]->options)->toBe($expected['fields'][0]->options);
})->with(['0', '1', 'field']);

it('preserves metadata in the legacy cache despite matching credential text', function (string $secret): void {
    $integration = new MetadataRoundTripFixture(['clientSecret' => $secret]);
    $expected = $integration->fetchConfig()->all();
    $integration->cache = ['settings' => (new IntegrationFormSettings($expected))->serialize()];

    expect($integration->getConfig()->all())->toEqual($expected)
        ->and($integration->createFieldMappingField(['dataKey' => 'fields'])['integrationFields'][0]['handle'])->toBe('field10');
})->with(['0', '1', 'field']);

it('preserves metadata from a legacy settings getter despite matching credential text', function (string $secret): void {
    $integration = new class(['clientSecret' => $secret]) extends MetadataRoundTripFixture {
        public function getFormSettings(bool $useCache = true): bool|IntegrationFormSettings
        {
            return new IntegrationFormSettings($this->fetchConfig()->all());
        }
    };

    expect($integration->getConfig()->all())->toEqual($integration->fetchConfig()->all());
})->with(['0', '1', 'field']);
