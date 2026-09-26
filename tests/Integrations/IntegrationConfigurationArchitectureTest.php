<?php

use verbb\formie\attributes\FormIntegrationSetting;
use verbb\formie\models\FormIntegration;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\integrations\helpdesk\Freshdesk;

it('hydrates only annotated properties even when a provider overrides its advertised allowlist', function () {
    $connection = new class extends Freshdesk {
        public function getFormSettingAttributes(): array { return ['apiKey', 'apiDomain', 'context']; }
    };
    $connection->apiKey = 'global-secret';
    $binding = FormIntegration::fromSettings($connection, ['enabled' => true, 'mapToContact' => true, 'apiKey' => 'attacker', 'apiDomain' => 'attacker.example', 'context' => ['bad' => true]]);
    $runtime = $binding->createRuntime($connection);
    expect($runtime->mapToContact)->toBeTrue()->and($runtime->apiKey)->toBe('global-secret')->and($runtime->context)->toBe([]);
});

it('isolates builder and execution state for repeated and concurrent form bindings', function () {
    $connection = new Freshdesk();
    $a = FormIntegration::fromSettings($connection, ['enabled' => true, 'mapToContact' => true])->createRuntime($connection);
    $b = FormIntegration::fromSettings($connection, ['enabled' => true, 'mapToContact' => false])->createRuntime($connection);
    $a->settingsContext->dataKey = 'contact';
    $a->context['deliveryWriteAccepted'] = true;
    expect($b->mapToContact)->toBeFalse()->and($a->mapToContact)->toBeTrue()->and($b->context)->toBe([])->and($connection->context)->toBe([])->and($b->settingsContext->dataKey)->toBeNull()->and($connection->settingsContext->dataKey)->toBeNull();
});

it('rejects arbitrary cached class construction while reading known stable metadata', function () {
    $settings = new IntegrationFormSettings();
    expect(fn() => $settings->unserialize(['class' => stdClass::class]))->toThrow(InvalidArgumentException::class);
    $settings->unserialize(['fields' => [['class' => IntegrationField::class, 'handle' => 'email', 'name' => 'Email']]]);
    expect($settings->getSettingsByKey('fields')[0])->toBeInstanceOf(IntegrationField::class);
    $stored = $settings->serialize();
    expect($stored['fields'][0]['_kind'])->toBe('field');
    $settings->unserialize($stored);
    expect($settings->getSettingsByKey('fields')[0]->handle)->toBe('email');
});

it('tracks metadata freshness and invalidation without caching secret properties', function () {
    $config = new IntegrationConfig(['apiKey' => 'secret', 'fields' => [new IntegrationField(['handle' => 'email'])]], 'connection-v1', 100);
    expect($config->data)->not->toHaveKey('apiKey')->and($config->isStale(101))->toBeFalse()->and($config->isStale(100 + IntegrationConfig::FRESH_SECONDS))->toBeTrue();
    expect(IntegrationConfig::fromStorage($config->toStorage(), 'connection-v2')->data)->toBe([]);
    expect(IntegrationConfig::fromStorage($config->toStorage(), 'connection-v1')->data)->toBe($config->data);
});

it('keeps inherited annotations authoritative and excludes static properties', function () {
    $connection = new class extends Freshdesk {
        #[FormIntegrationSetting]
        public string $custom = '';
        #[FormIntegrationSetting]
        public static string $global = '';
    };
    $names = FormIntegration::settingAttributes($connection);
    expect($names)->toContain('custom', 'mapToContact', 'optInField')->not->toContain('global', 'apiKey');
    expect(fn() => FormIntegration::fromSettings($connection, ['execution' => 'immediate']))->toThrow(InvalidArgumentException::class);
});


it('preserves every core form setting from the previous explicit allowlists', function () {
    $inventory = json_decode(file_get_contents(dirname(__DIR__) . '/fixtures/integrations/form-settings-attributes.json'), true);
    foreach ($inventory as $path => $expected) {
        $class = 'verbb\\formie\\' . str_replace('/', '\\', substr($path, 4, -4));
        $reflection = new ReflectionClass($class);
        foreach (array_diff($expected, ['enabled']) as $name) {
            expect($reflection->getProperty($name)->getAttributes(FormIntegrationSetting::class))->not->toBeEmpty($class . '::' . $name);
        }
    }
});
