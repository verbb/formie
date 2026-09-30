<?php

use verbb\formie\attributes\FormIntegrationSetting;
use verbb\formie\base\Integration;
use verbb\formie\helpers\StringHelper;
use verbb\formie\integrations\helpdesk\Freshdesk;
use verbb\formie\models\FormIntegration;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

class SensitiveSettingFixture extends Freshdesk
{
    #[\verbb\formie\attributes\Sensitive]
    #[FormIntegrationSetting]
    public string $opaque = '';
    public static function supportsConnection(): bool { return false; }
    public function fetchConfig(): IntegrationConfig {
        return new IntegrationConfig(['label' => $this->opaque, 'fields' => [new IntegrationField(['handle' => 'safe', 'name' => $this->opaque])]]);
    }
}

it('uses inherited sensitive metadata for non-obviously-named settings across storage and diagnostics', function() {
    $connection = new class(['name' => 'Sensitive metadata', 'handle' => 'sensitiveMetadata', 'opaque' => 'synthetic-private-value']) extends SensitiveSettingFixture {};
    $secrets = $connection->getDiagnosticSecrets();
    expect(\verbb\formie\helpers\IntegrationSecrets::sensitiveAttributes($connection))->toContain('opaque', 'apiKey', 'clientSecret');
    expect($secrets)->toContain('synthetic-private-value');
    $protected = \verbb\formie\helpers\IntegrationSecrets::protect(['opaque' => $connection->opaque], sensitiveAttributes: \verbb\formie\helpers\IntegrationSecrets::sensitiveAttributes($connection));
    expect(json_encode($protected))->not->toContain('synthetic-private-value')
        ->and(\verbb\formie\helpers\IntegrationSecrets::reveal($protected)['opaque'])->toBe($connection->opaque);
    expect(json_encode($connection->refreshConfig()->toStorage()))->not->toContain($connection->opaque);
    $connection->cache['config'] = (new IntegrationConfig(['oldLabel' => $connection->opaque], $connection->getIntegrationConfigKey(), time()))->toStorage();
    expect(json_encode($connection->getConfig()->toStorage()))->not->toContain($connection->opaque);
    $form = formie()->form()->create();
    $submission = formie()->submission($form)->save();
    $attempts = \verbb\formie\Formie::$plugin->getDeliveryAttempts();
    $uid = $attempts->prepare(new \verbb\formie\models\IntegrationExecutionContext($submission->id, $form->id, 'sensitiveMetadata', 'sensitive-test'), 'integration');
    $result = $attempts->execute($uid, fn() => new \verbb\formie\models\IntegrationResult(\verbb\formie\enums\IntegrationStatus::Failed, message: $connection->opaque, diagnostics: ['detail' => $connection->opaque]), $secrets);
    expect(json_encode($result->toStorage()))->not->toContain($connection->opaque)
        ->and(json_encode($attempts->supportBundle($uid)))->not->toContain($connection->opaque)
        ->and($attempts->get($uid)['result'])->not->toContain($connection->opaque);
});

it('encrypts annotated form-owned values when saving and exporting a form', function() {
    $connection = new SensitiveSettingFixture(['name' => 'Form secret', 'handle' => 'formSecret']);
    expect(\verbb\formie\Formie::$plugin->getIntegrations()->saveIntegration($connection, false))->toBeTrue();
    $form = formie()->form()->create();
    $form->settings->integrations = ['formSecret' => ['enabled' => true, 'opaque' => 'private-binding-value']];
    expect(Craft::$app->getElements()->saveElement($form, false))->toBeTrue();
    $stored = (new \craft\db\Query())->select('settings')->from(\verbb\formie\helpers\Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar();
    expect($stored)->not->toContain('private-binding-value');
    $export = \verbb\formie\models\StencilData::getSerializedFormSettings($form->settings);
    expect(json_encode($export))->not->toContain('private-binding-value');
    $revealed = \verbb\formie\helpers\IntegrationSecrets::reveal(json_decode($stored, true)['integrations']);
    expect($revealed['formSecret']['opaque'])->toBe('private-binding-value');
});

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
    expect($names)->toContain('custom', 'mapToContact')->not->toContain('global', 'apiKey', 'optInField', 'conditions');
    expect(fn() => FormIntegration::fromSettings($connection, ['execution' => 'immediate']))->toThrow(InvalidArgumentException::class);
});


it('preserves every core form setting from the previous explicit allowlists', function () {
    $inventory = json_decode(file_get_contents(dirname(__DIR__) . '/fixtures/integrations/form-settings-attributes.json'), true);
    foreach ($inventory as $path => $expected) {
        $class = 'verbb\\formie\\' . str_replace('/', '\\', substr($path, 4, -4));
        $reflection = new ReflectionClass($class);
        foreach (array_diff($expected, FormIntegration::POLICY_ATTRIBUTES) as $name) {
            expect($reflection->getProperty($name)->getAttributes(FormIntegrationSetting::class))->not->toBeEmpty($class . '::' . $name);
        }
    }
});

it('owns common policy on the binding while preserving Formie 3 property access', function() {
    $connection = new Freshdesk(['handle' => 'freshdesk', 'apiKey' => 'protected-key']);
    $binding = FormIntegration::fromSettings($connection, ['enabled' => true, 'execution' => 'synchronous', 'optInField' => '{field:consent}', 'enableConditions' => true, 'conditions' => ['conditions' => []], 'trigger' => ['policy' => 'onEdit'], 'mapToContact' => true, 'apiKey' => 'builder-value']);
    expect($binding->settings)->toBe(['mapToContact' => true])
        ->and($binding->trigger)->toBe(['policy' => 'onEdit']);
    $runtime = $binding->createRuntime();
    expect($runtime->apiKey)->toBe('protected-key')->and($runtime->optInField)->toBe('{field:consent}')
        ->and(FormIntegration::settingsFromRuntime($runtime)['execution'])->toBe('synchronous');
    $runtime->optInField = '{field:otherConsent}';
    expect($runtime->getFormIntegration()->optInField)->toBe('{field:otherConsent}')
        ->and($binding->optInField)->toBe('{field:consent}')->and($connection->optInField)->toBeNull();
});

it('migrates the old policy tree once without retaining a beta runtime alias', function() {
    $form = formie()->form()->create();
    $settings = (new \craft\db\Query())->select('settings')->from('{{%formie_forms}}')->where(['id' => $form->id])->scalar();
    $settings = \craft\helpers\Json::decode($settings);
    $settings['integrations']['demo'] = ['enabled' => true, 'fieldMapping' => ['name' => '{field:name}']];
    $settings['integrationPolicies'] = ['rerun' => ['demo' => ['policy' => 'onEdit']]];
    Craft::$app->getDb()->createCommand()->update('{{%formie_forms}}', ['settings' => \craft\helpers\Json::encode($settings)], ['id' => $form->id])->execute();
    $migration = new \verbb\formie\migrations\m260929_000000_form_integration_policy();
    ob_start();
    try { expect($migration->safeUp())->toBeTrue()->and($migration->safeUp())->toBeTrue(); } finally { ob_end_clean(); }
    $stored = \craft\helpers\Json::decode((new \craft\db\Query())->select('settings')->from('{{%formie_forms}}')->where(['id' => $form->id])->scalar());
    expect($stored)->not->toHaveKey('integrationPolicies')
        ->and($stored['integrations']['demo'])->toMatchArray(['enabled' => true, 'trigger' => ['policy' => 'onEdit'], 'fieldMapping' => ['name' => '{field:name}']]);
    expect(fn() => new \verbb\formie\models\FormSettings(['integrationPolicies' => []]))->toThrow(\yii\base\UnknownPropertyException::class);
});
