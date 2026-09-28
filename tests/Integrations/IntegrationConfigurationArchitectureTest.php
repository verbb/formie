<?php

use verbb\formie\attributes\FormIntegrationSetting;
use verbb\formie\base\Integration;
use verbb\formie\helpers\StringHelper;
use verbb\formie\integrations\helpdesk\Freshdesk;
use verbb\formie\models\FormIntegration;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

it('hydrates only annotated properties even when a provider overrides its advertised allowlist', function () {
    $connection = new class extends Freshdesk {
        public function getFormSettingAttributes(): array { return ['apiKey', 'apiDomain', 'context']; }
    };
    $connection->apiKey = 'global-secret';
    $binding = FormIntegration::fromSettings($connection, ['enabled' => true, 'mapToContact' => true, 'apiKey' => 'attacker', 'apiDomain' => 'attacker.example', 'context' => ['bad' => true]]);
    $runtime = $binding->createRuntime();
    expect($runtime->mapToContact)->toBeTrue()->and($runtime->apiKey)->toBe('global-secret')->and($runtime->context)->toBe([]);
});

it('isolates builder and execution state for repeated and concurrent form bindings', function () {
    $connection = new Freshdesk();
    $a = FormIntegration::fromSettings($connection, ['enabled' => true, 'mapToContact' => true])->createRuntime();
    $b = FormIntegration::fromSettings($connection, ['enabled' => true, 'mapToContact' => false])->createRuntime();
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
    expect(IntegrationConfig::fromStorage($config->toStorage(), 'connection-v1')->data)->toEqual($config->data);
});

it('restores emoji from integration cache shortcodes', function () {
    $connection = new class extends Integration {
        public static function displayName(): string { return 'Emoji Config'; }
    };
    $config = new IntegrationConfig(['label' => '😀'], $connection->getIntegrationConfigKey(), time());
    $stored = $config->toStorage();
    $stored['data']['label'] = StringHelper::emojiToShortcodes($stored['data']['label']);
    $connection->cache['config'] = $stored;

    expect($connection->getConfig()->get('label'))->toBe('😀');
});

it('adapts stable Formie 3 metadata providers into the canonical config model', function () {
    $connection = new class extends Integration {
        public static function displayName(): string { return 'Legacy Config'; }
        public static function supportsConnection(): bool { return false; }
        public function fetchFormSettings(): IntegrationFormSettings
        {
            return new IntegrationFormSettings(['fields' => [new IntegrationField(['handle' => 'email'])]]);
        }
    };

    $config = $connection->refreshConfig();

    expect($config)->toBeInstanceOf(IntegrationConfig::class)
        ->and($config->get('fields')[0])->toBeInstanceOf(IntegrationField::class)
        ->and($connection->getFormSettings())->toBeInstanceOf(IntegrationFormSettings::class);
});

it('prefers a Formie 3 subclass override over an inherited concrete provider config method', function () {
    $connection = new class extends Freshdesk {
        public static function supportsConnection(): bool { return false; }
        public function fetchFormSettings(): IntegrationFormSettings
        {
            return new IntegrationFormSettings(['legacySubclass' => true]);
        }
    };

    expect($connection->refreshConfig()->get('legacySubclass'))->toBeTrue();
});

it('keeps canonical and Formie 3 config events on one refresh path', function () {
    $connection = new class extends Integration {
        public static function displayName(): string { return 'Config Events'; }
        public static function supportsConnection(): bool { return false; }
        public function fetchConfig(): IntegrationConfig
        {
            return new IntegrationConfig(['base' => true]);
        }
    };
    $connection->on(Integration::EVENT_AFTER_FETCH_CONFIG, function ($event) {
        $event->config = new IntegrationConfig($event->config->all() + ['canonical' => true]);
    });
    $connection->on(Integration::EVENT_AFTER_FETCH_FORM_SETTINGS, function ($event) {
        $event->settings->setSettingsByKey('legacy', true);
    });

    $config = $connection->refreshConfig();

    expect($config->all())->toMatchArray(['base' => true, 'canonical' => true, 'legacy' => true])
        ->and($connection->getConfig()->all())->toEqual($config->all());
});

it('preserves the Formie 3 false return when a legacy refresh event cancels', function () {
    $connection = new class extends Integration {
        public static function displayName(): string { return 'Cancelled Config'; }
        public static function supportsConnection(): bool { return false; }
    };
    $connection->on(Integration::EVENT_BEFORE_FETCH_FORM_SETTINGS, function ($event) {
        $event->isValid = false;
    });

    expect($connection->getFormSettings(false))->toBeFalse();
});

it('rejects persisted schema fields without form-setting authority', function () {
    $connection = new Freshdesk();
    $compiled = ['fieldEntries' => [['path' => 'apiDomain', 'field' => ['$field' => 'text']]]];

    expect(fn() => FormIntegration::validateSchema($connection, $compiled))
        ->toThrow(InvalidArgumentException::class, 'unannotated property');
});

it('retains immutable form and connection identity on bindings', function () {
    $connection = new Freshdesk(['handle' => 'freshdesk']);
    $binding = FormIntegration::fromSettings($connection, ['enabled' => true, 'execution' => 'synchronous', 'mapToContact' => true], 42, 'contact');

    expect($binding->integration)->toBe($connection)
        ->and($binding->formId)->toBe(42)
        ->and($binding->formHandle)->toBe('contact')
        ->and($binding->toSettings())->toMatchArray(['enabled' => true, 'execution' => 'synchronous', 'mapToContact' => true]);
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
