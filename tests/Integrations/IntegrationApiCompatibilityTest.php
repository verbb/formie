<?php

use craft\base\SavableComponent;
use verbb\formie\attributes\FormIntegrationSetting;
use verbb\formie\base\Crm;
use verbb\formie\base\DispatchableIntegrationInterface;
use verbb\formie\base\Element;
use verbb\formie\base\FormInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\Payment;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\Formie;
use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\integrations\elements\Entry;
use verbb\formie\integrations\payments\Mollie;
use verbb\formie\models\BrowserModule;
use verbb\formie\models\BrowserModuleContext;
use verbb\formie\models\FormIntegration;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\IntegrationRunContext;
use verbb\formie\models\IntegrationRunResults;
use verbb\formie\services\IntegrationDispatcher;
use verbb\formie\services\Integrations;
use yii\base\InvalidConfigException;

class LegacyMetadataParentFixture extends Crm
{
    public int $fetches = 0;
    public static function supportsConnection(): bool { return false; }
    public function fetchConfig(): IntegrationConfig { ++$this->fetches; return new IntegrationConfig(['parent' => true]); }
}
class LegacyMetadataChildFixture extends LegacyMetadataParentFixture
{
    public int $legacyCalls = 0;
    public bool $fail = false;
    public function fetchFormSettings() {
        ++$this->legacyCalls;
        $settings = parent::fetchFormSettings();
        if ($this->fail) throw new RuntimeException('Metadata fixture failure');
        $settings->setSettingsByKey('extension', true);
        return $settings;
    }
}

it('preserves metadata parent calls from direct legacy acquisition and canonical refresh', function(bool $legacy) {
    $provider = new LegacyMetadataChildFixture();
    $before = 0; $after = 0;
    $provider->on(Integration::EVENT_BEFORE_FETCH_CONFIG, function() use (&$before) { ++$before; });
    $provider->on(Integration::EVENT_AFTER_FETCH_CONFIG, function() use (&$after) { ++$after; });
    $config = $legacy ? $provider->fetchFormSettings()->toConfig() : $provider->refreshConfig();
    expect($config->all())->toBe(['parent' => true, 'extension' => true])
        ->and($provider->legacyCalls)->toBe(1)->and($provider->fetches)->toBe(1)
        ->and($before)->toBe($legacy ? 0 : 1)->and($after)->toBe($legacy ? 0 : 1);
})->with([false, true]);

it('restores metadata compatibility guards after exceptions and honours a derived canonical override', function() {
    $provider = new LegacyMetadataChildFixture(['fail' => true]);
    expect(fn() => $provider->refreshConfig())->toThrow(RuntimeException::class, 'Metadata fixture failure');
    $provider->fail = false;
    expect($provider->refreshConfig()->get('extension'))->toBeTrue()->and($provider->legacyCalls)->toBe(2);
    $canonical = new class extends LegacyMetadataChildFixture {
        public function fetchConfig(): IntegrationConfig { return new IntegrationConfig(parent::fetchConfig()->all() + ['canonical' => true]); }
    };
    expect($canonical->refreshConfig()->all())->toBe(['parent' => true, 'canonical' => true])
        ->and($canonical->legacyCalls)->toBe(0)->and($canonical->fetches)->toBe(1);
});

it('adapts metadata parent calls on the real HubSpot provider without live requests', function() {
    $provider = new class extends HubSpot {
        public int $legacyCalls = 0;
        public static function supportsConnection(): bool { return false; }
        public function request(string $method, string $uri, array $options = []): mixed { return []; }
        public function fetchFormSettings(): IntegrationFormSettings {
            ++$this->legacyCalls;
            $settings = parent::fetchFormSettings();
            $settings->setSettingsByKey('customMetadata', true);
            return $settings;
        }
    };
    \Tests\Support\WebRequestTestHelper::withWebRequestContext(function() use ($provider) {
        expect($provider->refreshConfig()->get('customMetadata'))->toBeTrue()->and($provider->legacyCalls)->toBe(1);
    });
});

it('uses the shared metadata lifecycle for local element providers and keeps local metadata fresh', function() {
    $provider = new class extends Entry {
        public int $legacyCalls = 0;
        public function fetchFormSettings(): IntegrationFormSettings {
            ++$this->legacyCalls;
            $settings = parent::fetchFormSettings();
            $settings->setSettingsByKey('revision', $this->legacyCalls);
            return $settings;
        }
    };
    $before = 0; $after = 0; $legacyBefore = 0; $legacyAfter = 0;
    $provider->on(Integration::EVENT_BEFORE_FETCH_CONFIG, function() use (&$before) { ++$before; });
    $provider->on(Integration::EVENT_AFTER_FETCH_CONFIG, function() use (&$after) { ++$after; });
    $provider->on(Integration::EVENT_BEFORE_FETCH_FORM_SETTINGS, function() use (&$legacyBefore) { ++$legacyBefore; });
    $provider->on(Integration::EVENT_AFTER_FETCH_FORM_SETTINGS, function() use (&$legacyAfter) { ++$legacyAfter; });
    expect($provider->getConfig()->get('revision'))->toBe(1)
        ->and($provider->getConfig()->get('revision'))->toBe(2)
        ->and($provider->refreshConfig()->get('revision'))->toBe(3)
        ->and([$before, $after, $legacyBefore, $legacyAfter])->toBe([3, 3, 3, 3]);
    $provider->on(Integration::EVENT_BEFORE_FETCH_CONFIG, function($event) { $event->isValid = false; });
    expect($provider->refreshConfig()->get('revision'))->toBe(3)->and($provider->legacyCalls)->toBe(3)->and($after)->toBe(3);
});

it('does not retain removed local metadata keys or persist local acquisition to the connection', function() {
    $provider = new class(['name' => 'Local metadata', 'handle' => 'localMetadata' . bin2hex(random_bytes(4)), 'scope' => Integrations::SCOPE_SITE]) extends Element {
        public array $metadata = ['removed' => true];
        public function fetchConfig(): IntegrationConfig { return new IntegrationConfig($this->metadata); }
    };
    expect(Formie::$plugin->getIntegrations()->saveIntegration($provider, false))->toBeTrue();
    $storedCache = (new \craft\db\Query())->select('cache')->from('{{%formie_integrations}}')->where(['id' => $provider->id])->scalar();
    expect($provider->getConfig()->get('removed'))->toBeTrue();
    $provider->metadata = ['current' => true];
    expect($provider->getConfig()->all())->toBe(['current' => true])
        ->and((new \craft\db\Query())->select('cache')->from('{{%formie_integrations}}')->where(['id' => $provider->id])->scalar())->toBe($storedCache);
});

it('routes old amount calls through canonical major-unit overrides on ordinary providers', function() {
    $provider = new class extends Mollie {
        public int $calls = 0;
        public function getPaymentAmount(Submission $submission): string|int|float { ++$this->calls; return parent::getPaymentAmount($submission) + 5.5; }
        public function getFieldSetting(string $setting, mixed $default = null): mixed { return ['amountType' => Payment::VALUE_TYPE_FIXED, 'amountFixed' => '2'][$setting] ?? $default; }
    };
    expect($provider->getAmount(new Submission()))->toBe(7.5)->and($provider->calls)->toBe(1)
        ->and($provider->getPaymentAmount(new Submission()))->toBe(7.5)->and($provider->calls)->toBe(2);
});

it('honours old ordinary payment overrides and parent calls from both spellings', function(bool $legacy) {
    $provider = new class extends Mollie {
        public int $calls = 0;
        public function getAmount(Submission $submission): string|int|float { ++$this->calls; return parent::getAmount($submission) + 1; }
        public function getFieldSetting(string $setting, mixed $default = null): mixed { return ['amountType' => Payment::VALUE_TYPE_FIXED, 'amountFixed' => '2'][$setting] ?? $default; }
    };
    $method = $legacy ? 'getAmount' : 'getPaymentAmount';
    expect($provider->$method(new Submission()))->toBe(3)->and($provider->calls)->toBe(1);
})->with([false, true]);

class InterfaceOnlyIntegrationFixture extends SavableComponent implements DispatchableIntegrationInterface
{
    public function getFormSettingAttributes(): array { return []; }
    public function getFormSettingsSchema(FormInterface $form): array { return []; }
    public function getConfig(bool $useCache = true): IntegrationConfig { return new IntegrationConfig(); }
    public function refreshConfig(): IntegrationConfig { return new IntegrationConfig(); }
    public function supportsConfigRefresh(): bool { return false; }
    public function getBrowserModule(BrowserModuleContext $context): ?BrowserModule { return null; }
    public function getCpIconUrl(?string $distBaseUrl = null): string { return ''; }
    public function execute(IntegrationRunContext $context): IntegrationResult { return IntegrationResult::succeeded(); }
}

it('rejects interface-only implementations at registration creation save and binding boundaries', function() {
    $service = new Integrations();
    $service->on(Integrations::EVENT_REGISTER_INTEGRATIONS, function(RegisterIntegrationsEvent $event) { $event->crm[] = InterfaceOnlyIntegrationFixture::class; });
    expect(fn() => $service->getAllIntegrationTypes())->toThrow(InvalidConfigException::class, 'must extend')
        ->and(fn() => $service->createIntegration(InterfaceOnlyIntegrationFixture::class))->toThrow(InvalidConfigException::class)
        ->and(fn() => $service->saveIntegration(new InterfaceOnlyIntegrationFixture()))->toThrow(InvalidConfigException::class)
        ->and(fn() => FormIntegration::fromSettings(new InterfaceOnlyIntegrationFixture(), []))->toThrow(InvalidArgumentException::class);
    expect($service->createIntegration('missing\\ExampleProvider'))->toBeInstanceOf(\verbb\formie\models\MissingIntegration::class);
});

class ApiServiceConnectionFixture extends Crm
{
    #[FormIntegrationSetting]
    public string $destination = 'global';
    public function getCpIconUrl(?string $distBaseUrl = null): string { return ''; }
}
class LegacyIntegrationServiceFixture extends Integrations
{
    public array $connections = [];
    public array $calls = [];
    public bool $fail = false;
    public function getAllIntegrations(): array { return $this->connections; }
    public function getAllCaptchas(): array { return []; }
    protected function countCall(string $method): void { $this->calls[$method] = ($this->calls[$method] ?? 0) + 1; }
    public function getFormIntegrationByHandle(string $handle): ?IntegrationInterface {
        $this->countCall(__FUNCTION__); return parent::getFormIntegrationByHandle($handle);
    }
    public function getAllIntegrationsForForm(): array {
        $this->countCall(__FUNCTION__); return parent::getAllIntegrationsForForm();
    }
    public function getIntegrationSummariesForForm(): array {
        $this->countCall(__FUNCTION__); return parent::getIntegrationSummariesForForm();
    }
    public function getFormIntegrationDefinitionByHandle(string $handle): ?IntegrationInterface {
        $this->countCall(__FUNCTION__); return parent::getFormIntegrationDefinitionByHandle($handle);
    }
    public function getFormIntegrationsForForm(Form $form): array {
        $this->countCall(__FUNCTION__); return parent::getFormIntegrationsForForm($form);
    }
    public function getAllEnabledIntegrationsForForm(Form $form): array {
        $this->countCall(__FUNCTION__); return parent::getAllEnabledIntegrationsForForm($form);
    }
    public function populateIntegrationFromFormSettings(IntegrationInterface $integration, array $settings): IntegrationInterface {
        $this->countCall(__FUNCTION__); return parent::populateIntegrationFromFormSettings($integration, $settings);
    }
    public function createIntegrationConfig(IntegrationInterface $integration): array {
        $this->countCall(__FUNCTION__);
        $config = parent::createIntegrationConfig($integration);
        if ($this->fail) throw new RuntimeException('Service fixture failure');
        return $config + ['extension' => true];
    }
}

it('preserves renamed service overrides and parent calls from both public spellings', function(bool $legacy) {
    $provider = new ApiServiceConnectionFixture(['handle' => 'serviceFixture', 'name' => 'Fixture', 'enabled' => true]);
    $disabled = new ApiServiceConnectionFixture(['handle' => 'disabledFixture', 'name' => 'Disabled', 'enabled' => false]);
    $service = new LegacyIntegrationServiceFixture(['connections' => [$provider, $disabled]]);
    $form = formie()->form()->create();
    $form->settings->integrations = ['serviceFixture' => ['enabled' => true, 'destination' => 'form']];
    $cases = [
        ['getBuilderIntegrationByHandle', 'getFormIntegrationByHandle', ['serviceFixture']],
        ['getGroupedBuilderIntegrations', 'getAllIntegrationsForForm', []],
        ['getGroupedBuilderIntegrationSummaries', 'getIntegrationSummariesForForm', []],
        ['resolveIntegrationDefinitionByHandle', 'getFormIntegrationDefinitionByHandle', ['disabledFixture']],
        ['getEnabledFormIntegrations', 'getFormIntegrationsForForm', [$form]],
        ['getEnabledIntegrationRuntimes', 'getAllEnabledIntegrationsForForm', [$form]],
        ['createIntegrationRuntime', 'populateIntegrationFromFormSettings', [$provider, ['enabled' => true, 'destination' => 'runtime']]],
        ['createProjectConfig', 'createIntegrationConfig', [$provider]],
    ];
    foreach ($cases as [$canonical, $old, $args]) {
        $service->calls = [];
        $method = $legacy ? $old : $canonical;
        $result = $service->$method(...$args);
        expect($service->calls[$old])->toBe(1);
        match ($canonical) {
            'getBuilderIntegrationByHandle' => expect($result)->toBe($provider),
            'getGroupedBuilderIntegrations' => expect(array_merge(...array_values($result)))->toBe([$provider]),
            'getGroupedBuilderIntegrationSummaries' => expect(array_merge(...array_values($result))[0]['handle'])->toBe('serviceFixture'),
            'resolveIntegrationDefinitionByHandle' => expect($result)->toBe($disabled),
            'getEnabledFormIntegrations' => expect($result[0])->toBeInstanceOf(FormIntegration::class)->and($result[0]->settings['destination'])->toBe('form'),
            'getEnabledIntegrationRuntimes' => expect($result[0])->not->toBe($provider)->and($result[0]->destination)->toBe('form'),
            'createIntegrationRuntime' => expect($result)->not->toBe($provider)->and($result->destination)->toBe('runtime'),
            'createProjectConfig' => expect($result['extension'])->toBeTrue()->and($result['handle'])->toBe('serviceFixture'),
        };
        expect($provider->destination)->toBe('global');
    }
    expect($service->getBuilderIntegrationByHandle('disabledFixture'))->toBeNull();
})->with([false, true]);

it('restores service guards after failure and gives derived canonical overrides precedence', function() {
    $provider = new ApiServiceConnectionFixture(['name' => 'Guard', 'handle' => 'guardFixture']);
    $service = new LegacyIntegrationServiceFixture(['fail' => true]);
    expect(fn() => $service->createProjectConfig($provider))->toThrow(RuntimeException::class, 'Service fixture failure');
    $service->fail = false;
    expect($service->createProjectConfig($provider)['extension'])->toBeTrue()->and($service->calls['createIntegrationConfig'])->toBe(2);
    $canonical = new class extends LegacyIntegrationServiceFixture {
        public int $canonicalCalls = 0;
        public function createProjectConfig(IntegrationInterface $integration): array {
            ++$this->canonicalCalls;
            return parent::createProjectConfig($integration) + ['canonical' => true];
        }
    };
    expect($canonical->createProjectConfig($provider))->toHaveKey('canonical')->not->toHaveKey('extension')
        ->and($canonical->calls)->toBe([])->and($canonical->canonicalCalls)->toBe(1);
});

it('preserves result persistence overrides without losing results or double invoking parent calls', function(bool $legacy) {
    $dispatcher = new class extends IntegrationDispatcher {
        public int $loads = 0;
        public int $saves = 0;
        public function loadContext(Submission $submission, ?string $runUid = null): IntegrationRunResults {
            ++$this->loads; return parent::loadContext($submission, $runUid);
        }
        public function saveContext(Submission $submission, IntegrationRunResults $context, string $runUid): void {
            ++$this->saves; parent::saveContext($submission, $context, $runUid);
        }
    };
    $submission = formie()->submission(formie()->form()->create())->save();
    $results = new IntegrationRunResults();
    $results->record('contact', ['status' => 'succeeded', 'elementId' => 42]);
    $save = $legacy ? 'saveContext' : 'saveRunResults';
    $load = $legacy ? 'loadContext' : 'loadRunResults';
    $dispatcher->$save($submission, $results, 'compatibility-run');
    expect($dispatcher->saves)->toBe(1)->and($dispatcher->loads)->toBe(1);
    $dispatcher->loads = 0;
    expect($dispatcher->$load($submission, 'compatibility-run')->getResult('contact')['elementId'])->toBe(42)
        ->and($dispatcher->loads)->toBe(1)
        ->and($dispatcher->loadRunResults($submission, 'other-run')->getResult('contact'))->toBeNull();
})->with([false, true]);

it('preserves manual execution overrides while retaining operator authorization', function(bool $legacy) {
    $service = new class extends \verbb\formie\services\IntegrationTriggers {
        public int $calls = 0;
        public function dispatchManualIntegration(Integration $integration, Submission $submission): IntegrationResult {
            ++$this->calls;
            return parent::dispatchManualIntegration($integration, $submission);
        }
    };
    $provider = new class extends Crm {
        public static int $writes = 0;
        protected function executePayload(Submission $submission): IntegrationResult { ++self::$writes; return IntegrationResult::succeeded(); }
    };
    $provider::$writes = 0;
    $form = formie()->form()->create();
    $submission = formie()->submission($form)->save();
    \Tests\Support\WebRequestTestHelper::withWebRequestContext(function() use ($service, $provider, $submission, $legacy) {
        Craft::$app->getUser()->setIdentity(null);
        $method = $legacy ? 'dispatchManualIntegration' : 'runManualIntegration';
        expect(fn() => $service->$method($provider, $submission))->toThrow(\yii\web\ForbiddenHttpException::class);
        expect($service->calls)->toBe(1)->and($provider::$writes)->toBe(0);
    });
})->with([false, true]);

it('runs an authorized manual integration once through legacy overrides and records operator identity', function(bool $legacy) {
    $service = new class extends \verbb\formie\services\IntegrationTriggers {
        public int $calls = 0;
        public function dispatchManualIntegration(Integration $integration, Submission $submission): IntegrationResult {
            ++$this->calls; return parent::dispatchManualIntegration($integration, $submission);
        }
    };
    $provider = new class(['handle' => 'manualCompatibility', 'name' => 'Manual compatibility', 'enabled' => true]) extends Crm {
        public static int $writes = 0;
        protected function executePayload(Submission $submission): IntegrationResult { ++self::$writes; return IntegrationResult::succeeded(); }
    };
    $provider::$writes = 0;
    $submission = formie()->submission(formie()->form()->create())->save();
    \Tests\Support\WebRequestTestHelper::withWebRequestContext(function($request) use ($service, $provider, $submission, $legacy) {
        $actor = \craft\elements\User::find()->admin(true)->one();
        Craft::$app->getUser()->setIdentity($actor);
        $request->setIsCpRequest(true);
        $method = $legacy ? 'dispatchManualIntegration' : 'runManualIntegration';
        expect($service->$method($provider, $submission)->isSuccessful())->toBeTrue()
            ->and($service->calls)->toBe(1)->and($provider::$writes)->toBe(1);
        $attempt = (new \craft\db\Query())->from('{{%formie_delivery_attempts}}')->where(['submissionId' => $submission->id, 'binding' => $provider->handle, 'step' => 'integration'])->one();
        expect($attempt)->not->toBeFalse();
        $context = Formie::$plugin->getDeliveryAttempts()->context($attempt['uid']);
        expect($context->actorId)->toBe((int)$actor->id)->and($context->reason)->toBe('manual');
    });
})->with([false, true]);

it('preserves the deprecated submission-service return contract for boolean and response providers', function(bool $success, bool $response) {
    $provider = new class(['name' => 'Legacy caller', 'handle' => 'legacyCaller']) extends Integration {
        public bool $succeeds = true;
        public bool $asResponse = false;
        public function sendPayload(Submission $submission): bool|\verbb\formie\models\IntegrationResponse {
            return $this->asResponse ? new \verbb\formie\models\IntegrationResponse($this->succeeds, ['provider' => 'message']) : $this->succeeds;
        }
    };
    $provider->succeeds = $success;
    $provider->asResponse = $response;
    $submission = formie()->submission(formie()->form()->create())->save();
    $result = Formie::$plugin->getSubmissions()->sendIntegrationPayload($provider, $submission);
    if ($response) {
        expect($result)->toBeInstanceOf(\verbb\formie\models\IntegrationResponse::class)
            ->and($result->success)->toBe($success)->and($result->message)->toBe(['provider' => 'message']);
    } else {
        expect($result)->toBe($success);
    }
})->with([[true, false], [false, false], [true, true], [false, true]]);

it('acknowledges legacy service event cancellation without leaking an earlier provider response', function() {
    $provider = new class(['name' => 'Cancelled caller', 'handle' => 'cancelledCaller']) extends Integration {
        public int $calls = 0;
        public function sendPayload(Submission $submission): \verbb\formie\models\IntegrationResponse {
            ++$this->calls; return new \verbb\formie\models\IntegrationResponse(false, ['old' => 'failure']);
        }
    };
    $submission = formie()->submission(formie()->form()->create())->save();
    Formie::$plugin->getSubmissions()->sendIntegrationPayload($provider, $submission);
    $service = Formie::$plugin->getIntegrations();
    $cancel = function($event) { $event->isValid = false; };
    $service->on(Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, $cancel);
    try {
        expect(Formie::$plugin->getSubmissions()->sendIntegrationPayload($provider, $submission))->toBeTrue()->and($provider->calls)->toBe(1);
    } finally {
        $service->off(Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, $cancel);
    }
});

it('keeps acknowledged skips and uncertain outcomes distinct at the legacy result boundary', function() {
    $response = new \verbb\formie\models\IntegrationResponse(true, ['provider' => 'accepted']);
    expect(\verbb\formie\compatibility\integrations\IntegrationResultCompatibility::toLegacy(IntegrationResult::skipped('event_or_opt_in')))->toBeTrue();
    $unknown = \verbb\formie\compatibility\integrations\IntegrationResultCompatibility::toLegacy(IntegrationResult::unknown(), $response);
    expect($unknown->success)->toBeFalse()->and($unknown->message)->toBe($response->message)->and($response->success)->toBeTrue();
});
