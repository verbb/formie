<?php

declare(strict_types=1);

use craft\elements\User;
use craft\web\View;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;
use Psr\Http\Message\RequestInterface;
use Tests\Support\WebRequestTestHelper;
use verbb\auth\models\Token;
use verbb\formie\attributes\FormIntegrationSetting;
use verbb\formie\base\AddressProvider;
use verbb\formie\base\Captcha;
use verbb\formie\base\Integration;
use verbb\formie\base\Payment as PaymentProvider;
use verbb\formie\elements\Form;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\fields\Address;
use verbb\formie\fields\Payment as PaymentField;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\crm\Capsule;
use verbb\formie\integrations\elements\CalendarEvent;
use verbb\formie\integrations\miscellaneous\GoogleSheets;
use verbb\formie\integrations\miscellaneous\Trello;
use verbb\formie\models\BrowserModule;
use verbb\formie\models\BrowserModuleContext;
use verbb\formie\models\FormIntegration;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationSettingsContext;
use verbb\formie\services\Integrations;
use yii\base\Event;

// Every provider registered with Formie is booted through the same entry points the control panel
// and form builder use. Only network I/O is replaced. A provider that throws fails its own row, so
// a failure here is a finding about that provider: never weaken an assertion to get a row passing.
//
// Output from the stubbed responses is treated differently from everything else. Settings HTML, form
// settings and builder config use no response data, so any exception there is a failure. Fetching
// config depends on the response shape, which the stub can only approximate, so only errors a response
// can't cause fail that row, and anything else is skipped as inconclusive rather than passed.

/** A provider broke the contract. */
final class ProviderContractViolation extends AssertionFailedError
{
    public function __construct(string $message, public readonly ?Throwable $root = null)
    {
        parent::__construct($message);
    }
}

/** Replaces network I/O for the current test and records every request that would have been sent. */
final class ProviderContractHttp
{
    public const REQUEST_LIMIT = 250;

    public static array $requests = [];
    public static bool $connected = false;
    public static string $shape = 'map';
    public static array $handlers = [];
    public static int $saved = 0;
    public static array $apps = [];
    public static array $bodyParams = [];

    private static array $keysByClass = [];
    private static array $payloads = [];

    public static function install(): void
    {
        self::$requests = [];
        self::$connected = false;
        self::$shape = 'map';
        self::$bodyParams = [];

        // Every Craft::createGuzzleClient() call resolves here, including provider clients built outside defineClient().
        Craft::$container->set(Client::class, static function($container, array $params): Client {
            return new Client(array_merge($params[0] ?? [], ['handler' => self::handler()]));
        });
    }

    public static function uninstall(): void
    {
        foreach (self::$handlers as $handler) {
            Event::off(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, $handler);
        }

        self::$handlers = [];
        Craft::$container->clear(Client::class);
        $db = Craft::$app->getDb();

        foreach (self::$apps as $app) {
            foreach ($app->getComponents(false) as $component) {
                if ($component instanceof \yii\db\Connection && $component !== $db && $component !== Craft::$app->get('db2', false)) {
                    $component->close();
                }
            }
        }

        self::$apps = [];
    }

    public static function handler(): callable
    {
        return static function(RequestInterface $request): FulfilledPromise {
            self::record($request->getMethod(), (string)$request->getUri());

            return new FulfilledPromise(new Response(200, ['Content-Type' => 'application/json'], '{}'));
        };
    }

    public static function respond(Integration $integration, string $method, string $uri): array
    {
        self::record($method, $uri);

        return self::payload($integration);
    }

    public static function record(string $method, string $uri): void
    {
        self::$requests[] = strtoupper($method) . ' ' . $uri;

        if (count(self::$requests) > self::REQUEST_LIMIT) {
            throw new RuntimeException('The contract HTTP stub stopped after ' . self::REQUEST_LIMIT . ' requests.');
        }
    }

    /** Builds a response from the array keys the provider's own source reads, so no per-provider fixture is needed. */
    private static function payload(Integration $integration): array
    {
        if (self::$shape === 'empty') {
            return [];
        }

        $class = providerContractFileClass($integration::class);
        $node = self::$payloads[$class] ??= self::node(self::keys($class), 3);

        return self::$shape === 'list' ? [$node] : $node;
    }

    private static function node(array $keys, int $depth): array
    {
        $child = $depth > 0 ? self::node($keys, $depth - 1) : null;
        $node = [];

        foreach ($keys as $key => $isNested) {
            if ($child === null) {
                $node[$key] = "contract-{$key}";
            } elseif ($isNested) {
                $node[$key] = $child;
            } else {
                $isScalar = preg_match('/(^|_)(id|name|label|title|type|key|handle|slug|value|code|email|url|status|description|message|text|token)$|(Id|ID|Name|Label|Type|Key|Url)$/', $key) === 1;
                $node[$key] = $isScalar ? "contract-{$key}" : [$child];
            }
        }

        return $node;
    }

    /** @return array<string, bool> each key the source reads, and whether it's read with another key directly after it */
    private static function keys(string $class): array
    {
        if (isset(self::$keysByClass[$class])) {
            return self::$keysByClass[$class];
        }

        $source = (string)file_get_contents((new ReflectionClass($class))->getFileName());
        preg_match_all('/\[\s*[\'"]([A-Za-z_][\w-]*)[\'"]\s*\](?!\s*=(?!=))/', $source, $reads);
        preg_match_all('/\[\s*[\'"]([A-Za-z_][\w-]*)[\'"]\s*\]\[\s*[\'"]/', $source, $nested);
        preg_match_all('/getValue\([^,]+,\s*[\'"]([\w.-]+)[\'"]/', $source, $paths);
        $keys = array_fill_keys($reads[1], false);

        foreach ($paths[1] as $path) {
            $keys += array_fill_keys(explode('.', $path), false);
        }

        return self::$keysByClass[$class] = array_replace($keys, array_fill_keys($nested[1], true));
    }
}

/** Mixed into a generated subclass of each provider, overriding only what would reach the network or DNS. */
trait ProviderContractTransport
{
    public function request(string $method, string $uri, array $options = [], mixed ...$rest): mixed
    {
        return ProviderContractHttp::respond($this, $method, $uri);
    }

    public function requestPublicEndpoint(string $method, string $uri, array $options = []): mixed
    {
        return ProviderContractHttp::respond($this, $method, $uri);
    }

    public function requestWithProviderClient(Client $client, string $method, string $uri, array $options = []): mixed
    {
        return ProviderContractHttp::respond($this, $method, $uri);
    }

    public function getToken(): ?Token
    {
        if (!ProviderContractHttp::$connected) {
            return null;
        }

        $token = new Token();
        $token->setToken(new AccessToken(['access_token' => 'contract-token', 'expires' => time() + 3600]));

        return $token;
    }

    protected function requestWithOAuthProvider(string $method, string $uri, array $options = []): mixed
    {
        return ProviderContractHttp::respond($this, $method, $uri);
    }

    protected function createDeliveryHttpHandler(): callable
    {
        return ProviderContractHttp::handler();
    }

    protected function createPublicEndpointClient(string $endpoint, array $config = []): Client
    {
        return Craft::createGuzzleClient($config);
    }

    protected function isPublicHttpEndpoint(string $url): bool
    {
        return $url !== '';
    }
}

// Discovery
// =========================================================================

/** @return array<string, array<int, class-string<Integration>>> every registered class per type, before plugin requirements filter them */
function providerContractRegistered(): array
{
    $captured = null;
    $handler = static function(RegisterIntegrationsEvent $event) use (&$captured): void {
        $captured = [
            Integration::TYPE_ADDRESS_PROVIDER => $event->addressProviders,
            Integration::TYPE_CAPTCHA => $event->captchas,
            Integration::TYPE_ELEMENT => $event->elements,
            Integration::TYPE_EMAIL_MARKETING => $event->emailMarketing,
            Integration::TYPE_CRM => $event->crm,
            Integration::TYPE_HELP_DESK => $event->helpDesk,
            Integration::TYPE_MESSAGING => $event->messaging,
            Integration::TYPE_PAYMENT => $event->payments,
            Integration::TYPE_AUTOMATION => $event->automations,
            Integration::TYPE_MISC => $event->miscellaneous,
        ];
    };

    // A new service has no cached types, and the event fires with the full lists before plugin filtering.
    Event::on(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, $handler);

    try {
        (new Integrations())->getAllIntegrationTypes();
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, $handler);
    }

    return $captured ?? [];
}

function providerContractDataset(?string $baseClass = null): array
{
    $dataset = [];

    foreach (providerContractRegistered() as $type => $classes) {
        foreach ($classes as $class) {
            if ($baseClass === null || is_subclass_of($class, $baseClass)) {
                $dataset["{$type}/" . (new ReflectionClass($class))->getShortName()] = [$class];
            }
        }
    }

    return $dataset;
}

// Test-local provider classes
// =========================================================================

function providerContractSourceClass(string $class): string
{
    while (str_contains($class, '\\contractstub\\')) {
        $class = (string)get_parent_class($class);
    }

    return $class;
}

/** The nearest class with a source file, since generated classes have none to read. */
function providerContractFileClass(string $class): string
{
    while (str_contains((string)(new ReflectionClass($class))->getFileName(), "eval()'d")) {
        $class = (string)get_parent_class($class);
    }

    return $class;
}

/** Keeps the provider's short class name, because templates, icons and handles derive from it. */
function providerContractStubClass(string $class): string
{
    $short = (new ReflectionClass($class))->getShortName();
    $namespace = 'verbb\\formie\\integrations\\contractstub\\n' . md5($class);
    $stub = "{$namespace}\\{$short}";

    if (!class_exists($stub, false)) {
        eval("namespace {$namespace}; class {$short} extends \\{$class} { use \\ProviderContractTransport; }");
    }

    return $stub;
}

/** A provider with one method replaced, for the negative controls that prove these checks can fail. */
function providerContractBrokenClass(string $class, string $label, string $body): string
{
    $short = (new ReflectionClass($class))->getShortName();
    $namespace = 'verbb\\formie\\integrations\\contractbroken\\' . $label;
    $broken = "{$namespace}\\{$short}";

    if (!class_exists($broken, false)) {
        eval("namespace {$namespace}; class {$short} extends \\{$class} { {$body} }");
    }

    return $broken;
}

function providerContractMissingPlugins(string $class): array
{
    $missing = [];

    foreach ((array)$class::getRequiredPlugins() as $plugin) {
        $handle = is_array($plugin) ? ($plugin['handle'] ?? '') : $plugin;

        if (!Craft::$app->getPlugins()->isPluginEnabled((string)$handle)) {
            $missing[] = (string)$handle;
        }
    }

    return $missing;
}

/** Fills connection settings so paths guarded by hasValidSettings() run. */
function providerContractCredentials(string $class): array
{
    $settings = [];

    foreach ((new ReflectionClass($class))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
        $declaring = $property->getDeclaringClass()->getName();

        if ($property->isStatic() || $property->getAttributes(FormIntegrationSetting::class) || !str_starts_with($declaring, 'verbb\\formie\\integrations\\') || in_array($property->getName(), ['handle', 'name', 'spamReason'], true)) {
            continue;
        }

        $type = (string)$property->getType();

        if ($type === 'string' || $type === '?string') {
            $settings[$property->getName()] = preg_match('/url|domain|endpoint|host/i', $property->getName()) === 1 ? 'https://contract.example.test' : 'contract-value';
        }
    }

    if ($class::supportsOAuthConnection()) {
        $settings += ['clientId' => 'contract-client', 'clientSecret' => 'contract-secret'];
    }

    if (property_exists($class, 'questions')) {
        $settings['questions'] = [['question' => 'What is two plus three?', 'answers' => '5,five']];
    }

    return $settings;
}

/** Form-level settings as saved by the builder, filled from the provider's own declared attributes. */
function providerContractFormSettings(Integration $integration): array
{
    $settings = ['enabled' => true];

    foreach (FormIntegration::settingAttributes($integration) as $attribute) {
        $type = (string)(new ReflectionProperty($integration, $attribute))->getType();
        $value = match ($type) {
            'string', '?string' => 'contract-value',
            'bool', '?bool' => true,
            'array', '?array' => [],
            default => null,
        };

        if ($value !== null) {
            $settings[$attribute] = $value;
        }
    }

    return $settings;
}

function providerContractResetCaches(): void
{
    $integrations = Formie::$plugin->getIntegrations();
    (new ReflectionMethod($integrations, '_resetIntegrationCaches'))->invoke($integrations);
    Formie::$plugin->getCaptchaProviders()->resetCache();
    (new ReflectionProperty(PaymentField::class, '_paymentIntegrationsCache'))->setValue(null, null);
    Formie::$plugin->getRenderCache()->reset();
}

/** Registers the stub in place of the provider, so services looking up registered types find it. */
function providerContractRegister(string $class): string
{
    $stub = providerContractStubClass($class);

    if (isset(ProviderContractHttp::$handlers[$class])) {
        return $stub;
    }

    $handler = static function(RegisterIntegrationsEvent $event) use ($class, $stub): void {
        foreach (['addressProviders', 'captchas', 'elements', 'emailMarketing', 'crm', 'helpDesk', 'messaging', 'payments', 'automations', 'miscellaneous'] as $list) {
            $event->$list = array_map(static fn(string $registered): string => $registered === $class || is_subclass_of($class, $registered) ? $stub : $registered, $event->$list);
        }
    };

    Event::on(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, $handler);
    ProviderContractHttp::$handlers[$class] = $handler;
    providerContractResetCaches();

    return $stub;
}

/** Saves the provider through the service and returns it reloaded from its stored row. */
function providerContractLoad(string $class, bool $configured = true): Integration
{
    $stub = providerContractRegister($class);
    $integrations = Formie::$plugin->getIntegrations();
    $settings = $configured ? providerContractCredentials($class) : [];

    if (is_subclass_of($class, Captcha::class)) {
        return providerContractLoadCaptcha($class, $stub, $settings);
    }

    $handle = 'contract' . substr(md5($class), 0, 10) . (++ProviderContractHttp::$saved);
    $provider = $integrations->createIntegration([
        'type' => $stub,
        'name' => $class::displayName(),
        'handle' => $handle,
        'scope' => Integrations::SCOPE_SITE,
        'enabled' => true,
        'settings' => $settings,
    ]);

    if (!$integrations->saveIntegration($provider, false)) {
        throw new RuntimeException("Could not save a {$class} row: " . json_encode($provider->getErrors()));
    }

    providerContractResetCaches();

    return $integrations->getIntegrationByHandle($handle) ?? throw new RuntimeException("Could not reload the {$class} row.");
}

function providerContractLoadCaptcha(string $class, string $stub, array $settings): Integration
{
    $integrations = Formie::$plugin->getIntegrations();
    $handle = (new $class())->getHandle();

    // A provider with a missing plugin isn't registered, so nothing seeds its row or loads it.
    if (providerContractMissingPlugins($class)) {
        return $integrations->createIntegration(['type' => $stub, 'handle' => $handle, 'enabled' => true, 'scope' => Integrations::SCOPE_SITE, 'settings' => $settings]);
    }

    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_CAPTCHA_PROVIDERS, ['type' => $stub], ['handle' => $handle])->execute();
    providerContractResetCaches();
    $captcha = $integrations->getCaptchaByHandle($handle) ?? throw new RuntimeException("{$class} has no provider row.");
    $captcha->setEnabled(true);
    Craft::configure($captcha, $settings);

    if (!$integrations->saveCaptcha($captcha)) {
        throw new RuntimeException("Could not save the {$class} row: " . json_encode($captcha->getErrors()));
    }

    providerContractResetCaches();

    return $integrations->getCaptchaByHandle($handle);
}

/** Saved integration settings skip validation, as the filler can't know each provider's required or allowed values. */
function providerContractSavedForm(array $settings = [], ?callable $configure = null, array $integrations = []): Form
{
    $factory = formie()->form()
        ->singleLineTextField('firstName')
        ->emailField('emailAddress')
        ->agreeField('optIn')
        ->settings(array_merge(['disableCaptchas' => false], $settings));

    if ($configure) {
        $configure($factory);
    }

    $created = $factory->create();

    if ($integrations) {
        $created->settings->integrations = $integrations;

        if (!Craft::$app->getElements()->saveElement($created, false)) {
            throw new RuntimeException('Could not save integration settings on the form: ' . json_encode($created->getErrors()));
        }
    }

    return Form::find()->id($created->id)->one() ?? throw new RuntimeException('The saved form could not be reloaded.');
}

/** Rolling back releases locks taken during a request, reopening its lock connection, so these are closed after each test. */
function providerContractInRequest(callable $callback): mixed
{
    return WebRequestTestHelper::withWebRequestContext(static function(...$arguments) use ($callback): mixed {
        ProviderContractHttp::$apps[] = Craft::$app;

        return $callback(...$arguments);
    }, ['requestUri' => '/admin/formie/integrations', 'bodyParams' => ProviderContractHttp::$bodyParams]);
}

function providerContractInSite(callable $callback): mixed
{
    return providerContractInRequest(static function() use ($callback): mixed {
        Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_SITE);

        return $callback();
    });
}

function providerContractInControlPanel(callable $callback): mixed
{
    return providerContractInRequest(static function($request) use ($callback): mixed {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        $view = Craft::$app->getView();
        $view->registerTwigExtension(new \verbb\base\web\twig\Extension());
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);

        return $callback();
    });
}

// Reporting
// =========================================================================

function providerContractRootCause(Throwable $error): Throwable
{
    while ($error->getPrevious()) {
        $error = $error->getPrevious();
    }

    return $error;
}

/** Errors in the code itself. A response can't cause these, whatever it contains. */
function providerContractIsCodeError(Throwable $root): bool
{
    return preg_match('/^(Call to undefined (method|function)|Calling unknown method|Getting unknown property|Setting unknown property|Class ".+" not found|Interface ".+" not found|Trait ".+" not found|Undefined constant|Undefined variable|Call to (private|protected) |Cannot access (private|protected) |Too few arguments|Cannot instantiate|Access to undeclared static property)/', $root->getMessage()) === 1;
}

/** A provider's plugin isn't installed here, which isn't a fault in the provider. */
function providerContractBlockedBy(string $class, Throwable $root): ?array
{
    $missing = providerContractMissingPlugins(providerContractSourceClass($class));

    if (!$missing) {
        return null;
    }

    $isClassError = preg_match('/^Class "(?!verbb\\\\formie\\\\)[^"]+" not found/', $root->getMessage()) === 1;
    $isPluginInstance = preg_match('/^(Call to a member function \w+\(\) on null|Attempt to read property ".+" on null)/', $root->getMessage()) === 1;

    return $isClassError || $isPluginInstance ? $missing : null;
}

function providerContractDescribe(string $class, string $method, Throwable $error): string
{
    $root = providerContractRootCause($error);
    $location = $root->getFile() . ':' . $root->getLine();

    foreach ($root->getTrace() as $frame) {
        // The first frame in provider code says more than a vendor line, such as a Twig runtime helper.
        if (!str_contains($root->getFile(), '/vendor/') || !str_contains($frame['file'] ?? '', '/src/') || str_contains($frame['file'] ?? '', '/vendor/')) {
            continue;
        }

        $location .= ' (via ' . $frame['file'] . ':' . ($frame['line'] ?? 0) . ')';

        break;
    }

    return sprintf('%s::%s threw %s: %s at %s', providerContractSourceClass($class), $method, get_class($root), $root->getMessage(), str_replace(dirname(__DIR__, 2) . '/', '', $location));
}

/** Runs one provider call. Warnings count as errors here because Craft throws them in devMode. */
function providerContractCall(string $class, string $method, callable $callback): mixed
{
    set_error_handler(static function(int $level, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $level)) {
            return false;
        }

        throw new ErrorException($message, 0, $level, $file, $line);
    });

    try {
        return $callback();
    } catch (AssertionFailedError $error) {
        throw $error;
    } catch (Throwable $error) {
        $root = providerContractRootCause($error);
        $missing = providerContractBlockedBy($class, $root);

        if ($missing) {
            Assert::markTestSkipped(sprintf('%s::%s needs the %s plugin, which is not installed here: %s', providerContractSourceClass($class), $method, implode(', ', $missing), $root->getMessage()));
        }

        throw new ProviderContractViolation(providerContractDescribe($class, $method, $error), $root);
    } finally {
        restore_error_handler();
    }
}

function providerContractRequire(bool $condition, string $class, string $method, string $expectation): void
{
    if (!$condition) {
        throw new ProviderContractViolation(sprintf('%s::%s: expected %s', providerContractSourceClass($class), $method, $expectation));
    }

    Assert::assertTrue($condition);
}

// Checks
// =========================================================================

function providerContractCheckIdentity(string $class): void
{
    $stub = providerContractRegister($class);
    $types = array_keys(providerContractRegistered());

    foreach (['configured' => providerContractCredentials($class), 'unconfigured' => []] as $settings) {
        $provider = providerContractCall($class, 'createIntegration', static fn() => Formie::$plugin->getIntegrations()->createIntegration([
            'type' => $stub,
            'name' => $class::displayName(),
            'handle' => 'contractIdentity',
            'settings' => $settings,
        ]));

        providerContractRequire($provider instanceof $class, $class, 'createIntegration', 'the provider rather than a missing integration');

        foreach ([
            'displayName' => $class::displayName(...),
            'typeName' => $class::typeName(...),
            'getName' => $provider->getName(...),
            'getDescription' => $provider->getDescription(...),
            'getHandle' => $provider->getHandle(...),
            'getClassHandle' => $provider->getClassHandle(...),
            'getCategory' => $provider->getCategory(...),
            'getCategoryHandle' => $provider->getCategoryHandle(...),
            'getTypeHandle' => $provider->getTypeHandle(...),
        ] as $method => $read) {
            $value = providerContractCall($class, $method, $read);
            providerContractRequire(is_string($value) && trim($value) !== '', $class, $method, 'a non-empty string, got ' . var_export($value, true));
        }

        $type = providerContractCall($class, 'getType', $provider->getType(...));
        providerContractRequire(in_array($type, $types, true), $class, 'getType', 'a registered type, got ' . var_export($type, true));
        providerContractCall($class, 'getSettings', $provider->getSettings(...));
        providerContractCall($class, 'getFormSettingAttributes', $provider->getFormSettingAttributes(...));
        providerContractCall($class, 'hasValidSettings', $provider->hasValidSettings(...));
        providerContractCall($class, 'getIsConnected', $provider->getIsConnected(...));
    }
}

function providerContractCheckSettingsHtml(string $class): void
{
    foreach (['unconfigured' => false, 'configured' => true] as $state => $connected) {
        $provider = providerContractLoad($class, $connected);
        ProviderContractHttp::$connected = $connected;

        if ($connected && $class::supportsConnection()) {
            $provider->cache['connection'] = Integration::CONNECT_SUCCESS;
        }

        [$html, $hasNoSettings] = providerContractInControlPanel(static function() use ($class, $provider, $state): array {
            $templates = [];
            $listener = static function($event) use (&$templates): void {
                $templates[] = Craft::$app->getView()->resolveTemplate($event->template);
            };
            Event::on(View::class, View::EVENT_AFTER_RENDER_TEMPLATE, $listener);

            try {
                $html = providerContractCall($class, "getSettingsHtml ({$state})", $provider->getSettingsHtml(...));
            } finally {
                Event::off(View::class, View::EVENT_AFTER_RENDER_TEMPLATE, $listener);
            }

            // Providers with nothing to configure either inherit the empty default or ship an empty template.
            $inheritsDefault = (new ReflectionMethod($provider, 'getSettingsHtml'))->getDeclaringClass()->getName() === Integration::class;
            $emptyTemplate = $templates !== [] && !array_filter($templates, static fn($path): bool => !is_string($path) || trim((string)file_get_contents($path)) !== '');

            return [$html, $inheritsDefault || $emptyTemplate];
        });

        if ($hasNoSettings) {
            providerContractRequire(trim((string)$html) === '', $class, "getSettingsHtml ({$state})", 'no output from an empty template, got ' . var_export($html, true));
        } else {
            providerContractRequire(is_string($html) && str_contains($html, '<'), $class, "getSettingsHtml ({$state})", 'rendered HTML, got ' . var_export($html, true));
        }
    }
}

function providerContractCheckFormSettings(string $class): void
{
    $hasFormSettings = $class::hasFormSettings();
    $integrations = Formie::$plugin->getIntegrations();

    $provider = providerContractLoad($class);
    $handle = $provider->getHandle();

    foreach (['new form' => false, 'saved settings' => true] as $state => $withSettings) {
        $saved = $withSettings ? providerContractCall($class, 'getFormSettingAttributes', static fn() => providerContractFormSettings($provider)) : [];
        $form = providerContractSavedForm(integrations: $withSettings ? [$handle => $saved] : []);
        $method = "getIntegrationFormSettingsConfig ({$state})";

        $schema = providerContractCall($class, "getFormSettingsSchema ({$state})", static fn() => $provider->getFormSettingsSchema($form));
        providerContractRequire(is_array($schema) && $schema !== [], $class, "getFormSettingsSchema ({$state})", 'a schema');

        // The builder only asks for this when a provider has form settings. Providers without them must still answer safely.
        $config = providerContractInControlPanel(static fn() => providerContractCall($class, $method, static fn() => $integrations->getIntegrationFormSettingsConfig($handle, $form)));

        if (providerContractMissingPlugins($class) && is_subclass_of($class, Captcha::class)) {
            providerContractRequire($config === null, $class, $method, 'nothing for a captcha whose plugin is missing');

            continue;
        }

        providerContractRequire(is_array($config), $class, $method, 'a config array, got ' . var_export($config, true));
        providerContractRequire(!empty($config['schema']), $class, $method, 'a non-empty schema');

        if ($hasFormSettings) {
            $paths = array_column($config['schemaIndex']['fieldEntries'] ?? [], 'path');
            providerContractRequire(in_array('enabled', $paths, true), $class, $method, 'the schema to contain the enabled setting, got ' . json_encode($paths));

            if ($withSettings) {
                providerContractRequire(($config['defaultValues']['enabled'] ?? null) === true, $class, $method, 'saved settings to load from the reloaded form, got ' . json_encode($config['defaultValues']));
            }
        } else {
            providerContractRequire($config['defaultValues'] === [], $class, $method, 'no form settings for a provider without them, got ' . json_encode($config['defaultValues']));
        }
    }
}

function providerContractCheckBuilder(string $class): void
{
    $provider = providerContractLoad($class);
    $handle = $provider->getHandle();
    $form = providerContractSavedForm();
    $variables = providerContractInControlPanel(static fn() => providerContractCall($class, 'getFormBuilderVariables', static fn() => Formie::$plugin->getForms()->getFormBuilderVariables($form)));
    $summaries = $variables['canonicalData']['integrations'][$class::typeName()] ?? [];
    $summary = array_values(array_filter($summaries, static fn(array $row): bool => $row['handle'] === $handle))[0] ?? null;
    $isHidden = !$class::hasFormSettings() || (is_subclass_of($class, Captcha::class) && providerContractMissingPlugins($class));

    if ($isHidden) {
        providerContractRequire($summary === null, $class, 'getFormBuilderVariables', 'the provider to be left out of the builder');
    } else {
        providerContractRequire($summary !== null, $class, 'getFormBuilderVariables', "a builder summary for {$handle}");

        foreach (['name', 'displayName', 'classHandle', 'description', 'icon'] as $key) {
            providerContractRequire(is_string($summary[$key] ?? null) && $summary[$key] !== '', $class, 'getFormBuilderVariables', "a non-empty summary {$key}, got " . var_export($summary[$key] ?? null, true));
        }
    }

    foreach (providerContractCall($class, 'getOptionSourceDefinitions', $class::getOptionSourceDefinitions(...)) as $source) {
        $method = "getOptionSourceBuilderConfig ({$source['handle']})";
        $config = providerContractCall($class, $method, static fn() => $provider->getOptionSourceBuilderConfig($source['handle']));
        providerContractRequire(!empty($config['paramFields']), $class, $method, 'parameter fields, got ' . json_encode($config));
    }
}

/** The config keys a provider fetches separately. The builder takes them from the schema, but some are only compared in fetchConfig(). */
function providerContractDataKeys(string $class, array $schema): array
{
    $source = (string)file_get_contents((new ReflectionClass(providerContractFileClass($class)))->getFileName());
    preg_match_all('/dataKey(?:\s*===\s*|\'\s*=>\s*)\'([\w-]+)\'/', $source, $matches);
    $keys = $matches[1];

    array_walk_recursive($schema, static function($value, $key) use (&$keys): void {
        if ($key === 'dataKey' && is_string($value) && $value !== '') {
            $keys[] = $value;
        }
    });

    return array_merge([null], array_values(array_unique($keys)));
}

/** Whether the provider's own fetchConfig() sends requests, so that sending none means it was never really exercised. */
function providerContractFetchUsesHttp(string $class): bool
{
    $method = new ReflectionMethod(providerContractFileClass($class), 'fetchConfig');

    if ($method->getDeclaringClass()->getName() === Integration::class) {
        return false;
    }

    $lines = array_slice((array)file((string)$method->getFileName()), $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1);

    return preg_match('/->(request|requestPublicEndpoint|requestWithProviderClient)\(|getClient\(\)/', implode('', $lines)) === 1;
}

/**
 * Fetches config against each stubbed response shape, then repeats the builder reads against what it cached.
 *
 * Only code errors fail, as a response can't cause them. Anything else might be the stub's guess at the
 * response being wrong, so it's returned as inconclusive and the caller doesn't count it as passing.
 *
 * @return array{clean: list<string>, inconclusive: array<string, string>, requests: array<string, int>}
 */
function providerContractCheckFetchConfig(string $class): array
{
    $clean = [];
    $inconclusive = [];
    $requests = [];

    foreach (['map', 'list', 'empty'] as $shape) {
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            ProviderContractHttp::$shape = $shape;
            ProviderContractHttp::$requests = [];
            ProviderContractHttp::$connected = true;
            $provider = providerContractLoad($class);
            $handle = $provider->getHandle();

            if ($class::supportsConnection()) {
                $provider->cache['connection'] = Integration::CONNECT_SUCCESS;
            }

            // The same runtime the builder refreshes: saved form settings, then one refresh per data key.
            $settings = providerContractFormSettings($provider);
            $form = providerContractSavedForm(integrations: [$handle => $settings]);
            ProviderContractHttp::$bodyParams = ['integration' => $handle, 'formId' => (int)$form->id, 'settings' => $settings];
            $runtime = Formie::$plugin->getIntegrations()->createIntegrationRuntime($provider, $settings);
            $schema = providerContractInControlPanel(static fn() => providerContractCall($class, 'getFormSettingsSchema (saved settings)', static fn() => $runtime->getFormSettingsSchema($form)));
            $steps = [];

            foreach (providerContractDataKeys($class, $schema) as $dataKey) {
                $steps['refreshConfig (' . ($dataKey ?? 'no data key') . ')'] = static function() use ($runtime, $dataKey): mixed {
                    $runtime->settingsContext = new IntegrationSettingsContext(['dataKey' => $dataKey]);
                    ProviderContractHttp::$bodyParams['dataKey'] = $dataKey;

                    return providerContractInControlPanel($runtime->refreshConfig(...));
                };
            }

            $steps['getIntegrationFormSettingsConfig (fetched config)'] = static function() use ($handle, $form): void {
                providerContractResetCaches();
                $integrations = Formie::$plugin->getIntegrations();
                $reloaded = $integrations->getIntegrationByHandle($handle) ?? $integrations->getCaptchaByHandle($handle);

                if ($reloaded) {
                    providerContractInControlPanel(static function() use ($reloaded, $handle, $form, $integrations): void {
                        $reloaded->getFormSettingsSchema($form);
                        $integrations->getIntegrationFormSettingsConfig($handle, $form);
                    });
                }
            };

            foreach ($steps as $method => $step) {
                try {
                    $result = providerContractCall($class, $method, $step);

                    if (str_starts_with($method, 'refreshConfig')) {
                        providerContractRequire($result instanceof IntegrationConfig, $class, $method, 'an IntegrationConfig');
                    }
                } catch (ProviderContractViolation $violation) {
                    if ($violation->root === null || providerContractIsCodeError($violation->root)) {
                        throw $violation;
                    }

                    $inconclusive[$shape] = $violation->getMessage();

                    continue 2;
                }
            }

            $clean[] = $shape;
        } finally {
            $requests[$shape] = count(ProviderContractHttp::$requests);
            ProviderContractHttp::$bodyParams = [];
            $transaction->rollBack();
            providerContractResetCaches();
        }
    }

    return ['clean' => $clean, 'inconclusive' => $inconclusive, 'requests' => $requests];
}

function providerContractCheckPayment(string $class): void
{
    $provider = providerContractLoad($class);
    $handle = $provider->getHandle();
    $stub = get_class($provider);

    providerContractRequire($provider->hasValidSettings() === true, $class, 'hasValidSettings', 'the filled settings to be valid, otherwise the field template is never rendered');
    providerContractRequire(!$class::hasFormSettings(), $class, 'hasFormSettings', 'false, since payment providers are configured per field');

    $found = providerContractCall($class, 'getAllIntegrationsForType', static fn() => Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_PAYMENT));
    providerContractRequire(in_array($handle, array_map(static fn($row) => $row->handle, $found), true), $class, 'getAllIntegrationsForType', 'the saved provider');

    $form = providerContractSavedForm(['disableCaptchas' => true], static function($factory) use ($handle, $stub): void {
        $factory->paymentField('payment', [
            'paymentIntegration' => $handle,
            'paymentIntegrationType' => $stub,
            'providerSettings' => [$handle => ['amountType' => 'fixed', 'amountFixed' => '25.01', 'currencyType' => 'fixed', 'currencyFixed' => 'USD']],
        ]);
    });
    $field = $form->getFieldByHandle('payment');
    providerContractRequire($field instanceof PaymentField, $class, 'getFieldByHandle', 'the payment field');
    providerContractRequire($field->getPaymentIntegration() instanceof $class, $class, 'getPaymentIntegration', 'the field to resolve its provider');
    $bound = $field->getPaymentIntegration();

    foreach ([
        'getPaymentFieldSettingsDefaults' => $bound->getPaymentFieldSettingsDefaults(...),
        'getFieldHtmlVariables' => $bound->getFieldHtmlVariables(...),
        'getPaymentSubFields' => $field->getPaymentSubFields(...),
        'getSubscriptionCancellationModes' => $bound->getSubscriptionCancellationModes(...),
        'defineFormBuilderGeneralSchema' => $field->defineFormBuilderGeneralSchema(...),
        'defineFormBuilderSettingsSchema' => $field->defineFormBuilderSettingsSchema(...),
        'defineFormBuilderAppearanceSchema' => $field->defineFormBuilderAppearanceSchema(...),
    ] as $method => $read) {
        $value = providerContractInControlPanel(static fn() => providerContractCall($class, $method, $read));
        providerContractRequire(is_array($value), $class, $method, 'an array, got ' . get_debug_type($value));
    }

    foreach (['defineFormBuilderGeneralSchema', 'defineFormBuilderSettingsSchema', 'defineFormBuilderAppearanceSchema'] as $method) {
        providerContractInControlPanel(static fn() => providerContractCall($class, "provider {$method}", $bound->$method(...)));
    }

    providerContractCall($class, 'getPaymentEnvironment', $bound->getPaymentEnvironment(...));
    providerContractCall($class, 'getPaymentAccountFingerprint', $bound->getPaymentAccountFingerprint(...));

    if ($bound->supportsWebhooks()) {
        $webhookUrl = providerContractInControlPanel(static fn() => providerContractCall($class, 'getWebhookUrl', $bound->getWebhookUrl(...)));
        providerContractRequire(is_string($webhookUrl) && $webhookUrl !== '', $class, 'getWebhookUrl', 'a URL');
    }

    $html = providerContractInSite(static function() use ($class, $field, $form): array {
        return [
            'field' => providerContractCall($class, 'renderFieldHtml', static fn() => (string)$field->getPaymentHtml()),
            'form' => providerContractCall($class, 'renderForm', static fn() => (string)Formie::$plugin->getRendering()->renderForm($form, ['renderCss' => false, 'renderJs' => false])),
        ];
    });

    providerContractRequire(trim($html['field']) !== '', $class, 'renderFieldHtml', 'the field template output');
    providerContractRequire(str_contains($html['form'], '<form'), $class, 'renderForm', 'a rendered form containing the payment field');
}

function providerContractCheckCaptcha(string $class): void
{
    $provider = providerContractLoad($class);
    $handle = $provider->getHandle();
    $integrated = !providerContractMissingPlugins($class);
    $form = providerContractSavedForm(integrations: $integrated ? [$handle => ['enabled' => true]] : []);
    providerContractRequire($provider->hasValidSettings() == true, $class, 'hasValidSettings', 'the filled settings to be valid');
    $gqlHandle = providerContractCall($class, 'getGqlHandle', $provider->getGqlHandle(...));
    providerContractRequire(is_string($gqlHandle) && $gqlHandle !== '', $class, 'getGqlHandle', 'a handle');

    if ($integrated) {
        $enabled = providerContractCall($class, 'getAllEnabledCaptchasForForm', static fn() => Formie::$plugin->getIntegrations()->getAllEnabledCaptchasForForm($form));
        providerContractRequire(in_array($handle, array_map(static fn($row) => $row->handle, $enabled), true), $class, 'getAllEnabledCaptchasForForm', 'the enabled captcha');
    }

    providerContractInSite(static function() use ($class, $provider, $form, $integrated): void {
        $page = $form->getPages()[0] ?? null;
        $html = providerContractCall($class, 'renderHtml', static fn() => $provider->renderHtml($form, $page));
        providerContractRequire(is_string($html), $class, 'renderHtml', 'a string');
        $refresh = providerContractCall($class, 'getRefreshData', static fn() => $provider->getRefreshData($form, $page));
        providerContractRequire(is_array($refresh), $class, 'getRefreshData', 'an array');
        $module = providerContractCall($class, 'getBrowserModule', static fn() => $provider->getBrowserModule(new BrowserModuleContext(['form' => $form, 'integration' => $provider, 'page' => $page])));
        providerContractRequire($module === null || $module instanceof BrowserModule, $class, 'getBrowserModule', 'a BrowserModule or nothing');

        if ($integrated) {
            $form = providerContractCall($class, 'renderForm', static fn() => (string)Formie::$plugin->getRendering()->renderForm($form, ['renderCss' => false, 'renderJs' => false]));
            providerContractRequire(str_contains($form, '<form'), $class, 'renderForm', 'a rendered form');
        }
    });
}

function providerContractCheckAddressProvider(string $class): void
{
    $provider = providerContractLoad($class);
    $handle = $provider->getHandle();

    providerContractRequire($provider->hasValidSettings() === true, $class, 'hasValidSettings', 'the filled settings to be valid');
    providerContractRequire(!$class::hasFormSettings(), $class, 'hasFormSettings', 'false, since address providers are chosen per field');

    $rows = (new Address())->getSubFields();

    foreach ($rows as &$row) {
        foreach ($row['fields'] as &$child) {
            if ($child['handle'] === 'autoComplete') {
                $child['enabled'] = true;
                $child['integrationHandle'] = $handle;
            }
        }

        unset($child);
    }

    unset($row);

    $form = providerContractSavedForm(['disableCaptchas' => true], static function($factory) use ($rows): void {
        $factory->addressField('address', ['rows' => $rows]);
    });
    $field = $form->getFieldByHandle('address');
    providerContractRequire($field instanceof Address, $class, 'getFieldByHandle', 'the address field');
    $bound = providerContractCall($class, 'getAddressProviderIntegration', $field->getAddressProviderIntegration(...));
    providerContractRequire($bound instanceof $class, $class, 'getAddressProviderIntegration', 'the field to resolve its provider');
    providerContractCall($class, 'supportsCurrentLocation', $field->supportsCurrentLocation(...));

    $autoComplete = $field->getFieldByHandle('autoComplete');
    $schema = providerContractInControlPanel(static fn() => providerContractCall($class, 'defineFormBuilderGeneralSchema', $autoComplete->defineFormBuilderGeneralSchema(...)));
    providerContractRequire(str_contains((string)json_encode($schema), $handle), $class, 'defineFormBuilderGeneralSchema', "the provider picker to offer {$handle}");

    providerContractInSite(static function() use ($class, $bound, $field, $form): void {
        $html = providerContractCall($class, 'renderFieldHtml', static fn() => $bound->renderFieldHtml($field));
        providerContractRequire(is_string($html), $class, 'renderFieldHtml', 'a string');
        $module = providerContractCall($class, 'getBrowserModule', static fn() => $bound->getBrowserModule(new BrowserModuleContext(['form' => $form, 'field' => $field, 'integration' => $bound])));
        providerContractRequire($module instanceof BrowserModule, $class, 'getBrowserModule', 'the browser module that powers the field');
        $rendered = providerContractCall($class, 'renderForm', static fn() => (string)Formie::$plugin->getRendering()->renderForm($form, ['renderCss' => false, 'renderJs' => false]));
        providerContractRequire(str_contains($rendered, '<form'), $class, 'renderForm', 'a rendered form');
    });
}

// Tests
// =========================================================================

dataset('formie providers', static fn(): array => providerContractDataset());
dataset('formie payment providers', static fn(): array => providerContractDataset(PaymentProvider::class));
dataset('formie captcha providers', static fn(): array => providerContractDataset(Captcha::class));
dataset('formie address providers', static fn(): array => providerContractDataset(AddressProvider::class));

beforeEach(function(): void {
    ProviderContractHttp::install();
    $this->providerContractTransaction = Craft::$app->getDb()->beginTransaction();
});

afterEach(function(): void {
    $this->providerContractTransaction->rollBack();
    ProviderContractHttp::uninstall();
    Craft::$app->getInfo()->configVersion = (new \craft\db\Query())->select('configVersion')->from(\craft\db\Table::INFO)->scalar();
    Craft::$app->getProjectConfig()->reset();
    providerContractResetCaches();
});

it('discovers a plausible set of providers from the real registration', function(): void {
    $registered = providerContractRegistered();
    $classes = array_merge(...array_values($registered));

    expect(count($classes))->toBeGreaterThanOrEqual(100)
        ->and($classes)->toHaveCount(count(array_unique($classes)))
        ->and($classes)->toContain(Trello::class, GoogleSheets::class, Capsule::class, CalendarEvent::class)
        ->and(array_keys($registered))->toEqualCanonicalizing([
            Integration::TYPE_ADDRESS_PROVIDER, Integration::TYPE_CAPTCHA, Integration::TYPE_ELEMENT, Integration::TYPE_EMAIL_MARKETING, Integration::TYPE_CRM,
            Integration::TYPE_HELP_DESK, Integration::TYPE_MESSAGING, Integration::TYPE_PAYMENT, Integration::TYPE_AUTOMATION, Integration::TYPE_MISC,
        ])
        ->and(providerContractDataset())->toHaveCount(count($classes));

    foreach ($registered as $type => $list) {
        expect($list)->not->toBeEmpty("No {$type} providers were registered.");
    }

    // The per-category tests run against these same lists, so an empty one would pass by running nothing.
    expect(providerContractDataset(PaymentProvider::class))->toHaveCount(count($registered[Integration::TYPE_PAYMENT]))
        ->and(providerContractDataset(Captcha::class))->toHaveCount(count($registered[Integration::TYPE_CAPTCHA]))
        ->and(providerContractDataset(AddressProvider::class))->toHaveCount(count($registered[Integration::TYPE_ADDRESS_PROVIDER]));
});

it('includes a provider added by another plugin', function(): void {
    $added = providerContractBrokenClass(Trello::class, 'discovered', '');
    $handler = static function(RegisterIntegrationsEvent $event) use ($added): void {
        $event->miscellaneous[] = $added;
    };
    Event::on(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, $handler);

    try {
        expect(array_column(providerContractDataset(), 0))->toContain($added);
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, $handler);
    }
});

it('sends no request past the stub', function(): void {
    $clientResponse = Craft::createGuzzleClient(['base_uri' => 'https://contract.invalid/'])->request('GET', 'clients');
    $stub = providerContractStubClass(Capsule::class);
    $provider = new $stub(['name' => 'Capsule', 'handle' => 'capsule', 'apiKey' => 'key']);
    $fromDefinedClient = $provider->getClient()->request('GET', 'defined');
    $fromRequest = $provider->request('GET', 'requested');
    $fromPublicEndpoint = $provider->requestPublicEndpoint('GET', 'https://contract.invalid/public');
    $fromProviderClient = $provider->requestWithProviderClient(Craft::createGuzzleClient(), 'GET', 'https://contract.invalid/provider');

    expect($clientResponse->getStatusCode())->toBe(200)
        ->and($fromDefinedClient->getStatusCode())->toBe(200)
        ->and($fromRequest)->toBeArray()
        ->and($fromPublicEndpoint)->toBeArray()
        ->and($fromProviderClient)->toBeArray()
        ->and(ProviderContractHttp::$requests)->toHaveCount(5);
});

it('fails when a provider breaks the contract', function(string $check, string $body, string $expected): void {
    $broken = providerContractBrokenClass(Trello::class, 'control' . md5($body), $body);

    expect(static fn() => $check($broken))->toThrow(ProviderContractViolation::class, $expected);
})->with([
    'missing method in the schema' => ['providerContractCheckFormSettings', 'protected function defineFormSettingsSchema(\verbb\formie\base\FormInterface $form): array { return $this->_missingOptions(); }', 'Calling unknown method'],
    'empty schema' => ['providerContractCheckFormSettings', 'public function getFormSettingsSchema(\verbb\formie\base\FormInterface $form): array { return []; }', 'expected a schema'],
    'schema warning' => ['providerContractCheckFormSettings', 'protected function defineFormSettingsSchema(\verbb\formie\base\FormInterface $form): array { foreach (null as $row) {} return []; }', 'foreach() argument must be'],
    'missing settings template' => ['providerContractCheckSettingsHtml', 'public function getSettingsHtml(): ?string { return \Craft::$app->getView()->renderTemplate("formie/integrations/miscellaneous/contract-missing/_plugin-settings"); }', 'Unable to find the template'],
    'empty settings output' => ['providerContractCheckSettingsHtml', 'public function getSettingsHtml(): ?string { return ""; }', 'expected rendered HTML'],
    'empty description' => ['providerContractCheckIdentity', 'public function getDescription(): string { return ""; }', 'expected a non-empty string'],
    'builder summary throws' => ['providerContractCheckBuilder', 'public function getCpIconUrl(?string $distBaseUrl = null): string { throw new \LogicException("broken icon"); }', 'broken icon'],
    'undefined variable while fetching' => ['providerContractCheckFetchConfig', 'public function fetchConfig(): \verbb\formie\models\IntegrationConfig { $rows = array_map(function($row) { return $missing["id"]; }, $this->request("GET", "boards")); return new \verbb\formie\models\IntegrationConfig(["boards" => $rows]); }', 'Undefined variable $missing'],
]);

it('does not count a response-shape error as passing', function(): void {
    $body = 'public function fetchConfig(): \verbb\formie\models\IntegrationConfig { throw new \TypeError("Cannot access offset of type string on string"); }';
    $result = providerContractCheckFetchConfig(providerContractBrokenClass(Trello::class, 'controlShape', $body));

    expect($result['clean'])->toBe([])
        ->and($result['inconclusive'])->toHaveCount(3);
});

it('boots with its identity readable', function(string $class): void {
    providerContractCheckIdentity($class);
})->with('formie providers');

it('renders its settings through the real template', function(string $class): void {
    providerContractCheckSettingsHtml($class);
})->with('formie providers');

it('builds form settings for a saved form', function(string $class): void {
    providerContractCheckFormSettings($class);
})->with('formie providers');

it('builds its form builder config', function(string $class): void {
    providerContractCheckBuilder($class);
})->with('formie providers');

it('fetches and reuses its config through the stubbed client', function(string $class): void {
    $result = providerContractCheckFetchConfig($class);

    // An empty response skips every loop over its data, so a run that only survives that hasn't shown much.
    if (array_diff($result['clean'], ['empty']) === []) {
        $this->markTestSkipped('Inconclusive, no stubbed response with data got through: ' . implode(' | ', array_unique($result['inconclusive'])));
    }

    if (providerContractFetchUsesHttp($class) && array_sum($result['requests']) === 0) {
        $this->markTestSkipped('Inconclusive, fetchConfig() sends requests but none were reached.');
    }

    expect($result['clean'])->not->toBeEmpty();
})->with('formie providers');

it('renders its payment field through the real field path', function(string $class): void {
    providerContractCheckPayment($class);
})->with('formie payment providers');

it('renders its captcha through the real form path', function(string $class): void {
    providerContractCheckCaptcha($class);
})->with('formie captcha providers');

it('renders its address autocomplete through the real field path', function(string $class): void {
    providerContractCheckAddressProvider($class);
})->with('formie address providers');
