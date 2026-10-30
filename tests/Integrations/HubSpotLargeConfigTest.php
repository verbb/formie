<?php

use craft\helpers\Json;
use craft\web\Request;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;

function hubSpotLargePortalOptions(): array
{
    return array_map(fn(int $index) => [
        'label' => str_repeat('Portal option label ', 8) . $index,
        'value' => (string)$index,
    ], range(1, 100));
}

function hubSpotLargeLegacyConfig(): array
{
    return ['forms' => array_map(fn(int $index) => [
        'class' => IntegrationCollection::class,
        'id' => 'portal__' . $index,
        'name' => 'Form ' . $index,
        'fields' => [[
            'class' => IntegrationField::class,
            'handle' => 'interest',
            'name' => 'Interest',
            'options' => hubSpotLargePortalOptions(),
        ]],
    ], range(1, 180)), 'contact' => []];
}

it('reads large HubSpot caches for editing and selected-form delivery', function(bool $legacy): void {
    $settings = hubSpotLargeLegacyConfig();
    expect(strlen(Json::encode($settings)))->toBeGreaterThan(3 * 1024 * 1024);
    $integration = new HubSpot(['formId' => 'portal__180']);
    $integration->cache = $legacy ? ['settings' => $settings] : ['config' => [
        'version' => IntegrationConfig::VERSION,
        'invalidationKey' => $integration->getIntegrationConfigKey(),
        'fetchedAt' => time(),
        'data' => IntegrationConfig::encode(IntegrationConfig::decode($settings)),
    ]];

    $forms = $integration->getConfigValue('forms');
    expect($forms)->toHaveCount(180)
        ->and($forms[179]->fields[0]->options)->toBe(hubSpotLargePortalOptions())
        ->and($integration->getValidators())->not->toBeEmpty();

    $flag = new ReflectionProperty(HubSpot::class, '_useSelectedFormSettings');
    $flag->setValue($integration, true);
    $selected = $integration->getConfigValue('forms');
    expect($selected)->toHaveCount(1)
        ->and($selected[0]->id)->toBe('portal__180')
        ->and($selected[0]->fields[0]->options)->toBe(hubSpotLargePortalOptions());
})->with(['legacy' => true, 'versioned' => false]);

it('refreshes and persists all forms in a large HubSpot portal', function(): void {
    $integration = new class(['name' => 'Large HubSpot', 'handle' => 'largeHubSpot']) extends HubSpot {
        public static function supportsConnection(): bool
        {
            return false;
        }

        public function request(string $method, string $uri, array $options = []): mixed
        {
            return match ($uri) {
                'crm-object-schemas/v3/schemas' => ['results' => []],
                'forms/v2/forms' => array_map(fn(int $index) => [
                    'portalId' => 'portal',
                    'guid' => (string)$index,
                    'name' => sprintf('Form %03d', $index),
                    'formFieldGroups' => [['fields' => [[
                        'name' => 'interest',
                        'label' => 'Interest',
                        'propertyObjectType' => 'CONTACT',
                        'type' => 'enumeration',
                        'fieldType' => 'select',
                        'options' => hubSpotLargePortalOptions(),
                    ]]]],
                ], range(1, 180)),
                default => throw new RuntimeException('Unexpected HubSpot request: ' . $uri),
            };
        }
    };
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $integration->cache = ['settings' => hubSpotLargeLegacyConfig()];
    $request = Craft::$app->getRequest();
    $refreshRequest = new Request();
    $refreshRequest->setBodyParams(['refreshForms' => true]);
    Craft::$app->set('request', $refreshRequest);

    try {
        $config = $integration->refreshConfig();
        $fields = array_column($config->get('forms')[179]->fields, null, 'handle');
        expect($config->get('forms'))->toHaveCount(180)
            ->and($fields['interest']->options['options'])->toBe(hubSpotLargePortalOptions())
            ->and($config->get('contact'))->toBe([]);

        $stored = (new \craft\db\Query())->select('cache')->from(Table::FORMIE_INTEGRATIONS)->where(['id' => $integration->id])->scalar();
        $integration->cache = Json::decode($stored);
        $fields = array_column($integration->getConfigValue('forms')[179]->fields, null, 'handle');
        expect($integration->getConfigValue('forms'))->toHaveCount(180)
            ->and($fields['interest']->options['options'])->toBe(hubSpotLargePortalOptions());
    } finally {
        Craft::$app->set('request', $request);
        Formie::$plugin->getIntegrations()->deleteIntegrationById($integration->id);
    }
});

it('retains the integration metadata size boundary', function(): void {
    $overhead = strlen(json_encode(['label' => ''], JSON_THROW_ON_ERROR));
    $data = ['label' => str_repeat('a', IntegrationConfig::MAX_BYTES - $overhead)];
    expect((new IntegrationConfig($data))->get('label'))->toBe($data['label']);
    $data['label'] .= 'a';
    expect(fn() => new IntegrationConfig($data))->toThrow(InvalidArgumentException::class, 'Integration metadata exceeds the storage limit.');
});
