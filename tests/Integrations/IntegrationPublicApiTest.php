<?php

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\auth\models\Token;
use verbb\formie\base\Automation;
use verbb\formie\base\Captcha;
use verbb\formie\base\Crm;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\Payment;
use verbb\formie\controllers\IntegrationsController;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\IntegrationConnectionEvent;
use verbb\formie\Formie;
use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\integrations\payments\Opayo;
use verbb\formie\integrations\payments\Stripe;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\services\Integrations;
use yii\web\BadRequestHttpException;
use yii\web\MethodNotAllowedHttpException;

class PublicApiConnectionFixture extends Crm
{
    public bool $connected = false;
    public int $checks = 0;
    // Older integrations may omit native return types on these formerly implicit hooks.
    public function getDescription() { return 'Legacy description'; }
    public function fetchConnection() { ++$this->checks; return $this->connected; }
}

class PublicApiAutomationFixture extends Automation
{
    public static int $writes = 0;
    public static function supportsConnection(): bool { return false; }
    protected function executePayload(Submission $submission): IntegrationResult
    {
        ++self::$writes;
        return IntegrationResult::succeeded();
    }
}

it('returns actual connection failure while preserving the legacy event status', function() {
    $integration = new PublicApiConnectionFixture();
    $statuses = [];
    $integration->on(Integration::EVENT_AFTER_CHECK_CONNECTION, function(IntegrationConnectionEvent $event) use (&$statuses) {
        $statuses[] = $event->success;
    });
    expect($integration->checkConnection(false))->toBeFalse()
        ->and($integration->getIsConnected())->toBeFalse();
    $integration->connected = true;
    expect($integration->checkConnection(false))->toBeTrue()
        ->and($integration->getIsConnected())->toBeTrue()
        ->and($integration->checkConnection())->toBeTrue()
        ->and($integration->checks)->toBe(2)
        ->and($statuses)->toBe(['fail', 'success']);
});

it('honours connection cancellation and event-modified failures', function() {
    $integration = new PublicApiConnectionFixture(['connected' => true]);
    $integration->on(Integration::EVENT_BEFORE_CHECK_CONNECTION, function($event) { $event->isValid = false; });
    expect($integration->checkConnection(false))->toBeFalse()->and($integration->checks)->toBe(0);
    $integration->off(Integration::EVENT_BEFORE_CHECK_CONNECTION);
    $integration->on(Integration::EVENT_AFTER_CHECK_CONNECTION, function($event) { $event->success = Integration::CONNECT_FAIL; });
    expect($integration->checkConnection(false))->toBeFalse()->and($integration->getIsConnected())->toBeFalse();
});

it('defines safe connection defaults including OAuth token presence', function() {
    $plain = new class extends Crm {};
    expect($plain->checkConnection(false))->toBeFalse()->and($plain->getDescription())->toBe('');
    $oauth = new class extends Crm {
        public ?Token $testToken = null;
        public static function supportsOAuthConnection(): bool { return true; }
        public function getToken(): ?Token { return $this->testToken; }
    };
    expect($oauth->checkConnection(false))->toBeFalse();
    $oauth->testToken = new Token();
    expect($oauth->checkConnection(false))->toBeTrue();
});

it('keeps automation metadata refresh free from test sends', function(string $class) {
    $integration = new $class();
    expect($integration->fetchConfig()->all())->toBe([])
        ->and($integration->refreshConfig()->all())->toBe([])
        ->and(json_encode($integration->getFormSettingsSchema(formie()->form()->create())))->toContain('testPayload');
})->with([
    \verbb\formie\integrations\automations\Zapier::class,
    \verbb\formie\integrations\automations\Make::class,
    \verbb\formie\integrations\automations\N8n::class,
    \verbb\formie\integrations\automations\Ifttt::class,
    \verbb\formie\integrations\automations\WebRequest::class,
]);

it('separates the explicit test-send route from metadata and requires a valid request', function(string $action, string $method, bool $csrf, ?string $exception, int $writes) {
    PublicApiAutomationFixture::$writes = 0;
    $integration = new PublicApiAutomationFixture(['name' => 'Test action', 'handle' => 'testAction' . bin2hex(random_bytes(5)), 'scope' => Integrations::SCOPE_SITE]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form()->create();
    WebRequestTestHelper::withWebRequestContext(function($request) use ($integration, $form, $action, $method, $csrf, $exception, $writes) {
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        $request->setIsCpRequest(true);
        $params = ['integration' => $integration->handle, 'formId' => $form->id, 'settings' => []];
        if ($csrf) $params[$request->csrfParam] = $request->getCsrfToken();
        if ($method === 'POST') $request->setBodyParams($params); else $request->setQueryParams($params);
        $controller = new IntegrationsController('integrations', Formie::$plugin);
        if ($exception) {
            expect(fn() => $controller->runAction($action))->toThrow($exception);
        } else {
            $response = $controller->runAction($action);
            expect($response->statusCode)->toBe(200);
            if ($action === 'send-test-payload') expect($response->data['success'])->toBeTrue()->and($response->data['status'])->toBe('succeeded');
        }
        expect(PublicApiAutomationFixture::$writes)->toBe($writes);
    }, ['method' => $method, 'headers' => ['Accept' => 'application/json']]);
})->with([
    'metadata' => ['form-settings', 'POST', true, null, 0],
    'explicit send' => ['send-test-payload', 'POST', true, null, 1],
    'read request' => ['send-test-payload', 'GET', false, MethodNotAllowedHttpException::class, 0],
    'missing csrf' => ['send-test-payload', 'POST', false, BadRequestHttpException::class, 0],
]);

it('honours legacy mapping overrides and parent calls exactly once from either API', function(bool $legacy) {
    $integration = new class extends HubSpot {
        public int $calls = 0;
        public function getIntegrationFieldMappingField(array $config = []): array {
            ++$this->calls;
            return parent::getIntegrationFieldMappingField($config) + ['extension' => true];
        }
    };
    $method = $legacy ? 'getIntegrationFieldMappingField' : 'createFieldMappingField';
    $schema = $integration->$method(['dataLabel' => 'Contacts', 'dataKey' => 'contact']);
    expect($schema['extension'])->toBeTrue()->and($integration->calls)->toBe(1);
})->with([false, true]);

it('honours legacy diagnostic hooks once and clears their guard after failure', function(bool $legacy) {
    $integration = new class extends Crm {
        public int $calls = 0;
        public bool $fail = true;
        public function populateQueueJobContext($submission, $endpoint, $payload, $method, $contentType): void {
            ++$this->calls;
            parent::populateQueueJobContext($submission, $endpoint, $payload, $method, $contentType);
            if ($this->fail) throw new RuntimeException('Fixture checkpoint failure');
        }
    };
    $method = $legacy ? 'populateQueueJobContext' : 'recordDeliveryRequest';
    $submission = new Submission();
    expect(fn() => $integration->$method($submission, 'contacts', [], 'POST', 'json'))->toThrow(RuntimeException::class);
    expect($integration->calls)->toBe(1);
    $integration->fail = false;
    $integration->$method($submission, 'contacts', [], 'POST', 'json');
    expect($integration->calls)->toBe(2);
})->with([false, true]);

it('honours legacy captcha refresh overrides including parent calls', function(bool $legacy) {
    $captcha = new class extends Captcha {
        public int $calls = 0;
        public function getRefreshJsVariables(Form $form, FieldLayoutPage $page = null): array {
            ++$this->calls;
            return parent::getRefreshJsVariables($form, $page) + ['challenge' => 'custom'];
        }
    };
    $method = $legacy ? 'getRefreshJsVariables' : 'getRefreshData';
    expect($captcha->$method(new Form()))->toBe(['challenge' => 'custom'])->and($captcha->calls)->toBe(1);
})->with([false, true]);

it('preserves payment getter overrides through canonical refresh and direct legacy calls', function(bool $legacy) {
    $payment = new PaymentModel();
    $integration = new class extends Stripe {
        public int $calls = 0;
        public int $nativeCalls = 0;
        public function getTransaction(PaymentModel $payment): void {
            ++$this->calls;
            parent::getTransaction($payment);
            $payment->status = PaymentModel::STATUS_SUCCEEDED;
        }
        protected function refreshPaymentState(PaymentModel $payment): void { ++$this->nativeCalls; }
    };
    $method = $legacy ? 'getTransaction' : 'refreshPayment';
    $integration->$method($payment);
    expect($payment->status)->toBe(PaymentModel::STATUS_SUCCEEDED)
        ->and($integration->calls)->toBe(1)->and($integration->nativeCalls)->toBe(1);
})->with([false, true]);

it('provides payment schema defaults without narrowing legacy return signatures', function() {
    $integration = new class extends Payment {
        public function defineFormBuilderGeneralSchema() { return [['name' => 'custom']]; }
        public function getPaymentSubFields($field) { return ['custom']; }
    };
    expect($integration->defineFormBuilderGeneralSchema())->toBe([['name' => 'custom']])
        ->and($integration->defineFormBuilderSettingsSchema())->toBe([])
        ->and($integration->defineFormBuilderAppearanceSchema())->toBe([])
        ->and($integration->getPaymentSubFields(new \verbb\formie\fields\Payment()))->toBe(['custom']);
});

it('preserves legacy GraphQL input extensions through the canonical spelling', function() {
    $integration = new class extends Payment {
        public int $calls = 0;
        public function getGraphqlPaymentInputFieldKeys(FieldInterface $field): array {
            ++$this->calls;
            return [...parent::getGraphqlPaymentInputFieldKeys($field), 'custom'];
        }
        protected function getOptionalGraphqlPaymentInputFieldKeys(): array { return ['legacyOptional']; }
    };
    expect($integration->getGqlPaymentInputFieldKeys(new \verbb\formie\fields\Payment()))->toBe(['legacyOptional', 'custom'])
        ->and($integration->calls)->toBe(1);
});

it('keeps canonical amounts in major units and provider amounts in API units', function(string $currency, string $major, int $minor) {
    $integration = new class extends Stripe {
        public array $settingsFixture = [];
        public function getFieldSetting(string $setting, mixed $default = null): mixed { return $this->settingsFixture[$setting] ?? $default; }
    };
    $integration->settingsFixture = ['amountType' => Payment::VALUE_TYPE_FIXED, 'amountFixed' => $major, 'currency' => $currency];
    $submission = new Submission();
    expect($integration->getProviderAmount($submission))->toBe($minor)
        ->and($integration->getAmount($submission))->toBe($minor)
        ->and($integration->getPaymentAmount($submission))->toBe($major);
})->with([['USD', '12.34', 1234], ['JPY', '500', 500], ['BHD', '1.234', 1234]]);

it('preserves provider-unit legacy amount overrides with parent calls', function(string $base) {
    $integration = $base === 'stripe' ? new class extends Stripe {
        public int $calls = 0;
        public function getAmount(Submission $submission): string|int|float { ++$this->calls; return parent::getAmount($submission) + 100; }
        public function getFieldSetting(string $setting, mixed $default = null): mixed { return ['amountType' => Payment::VALUE_TYPE_FIXED, 'amountFixed' => '2', 'currency' => 'USD'][$setting] ?? $default; }
    } : new class extends Opayo {
        public int $calls = 0;
        public function getAmount(Submission $submission): string|int|float { ++$this->calls; return parent::getAmount($submission) + 100; }
        public function getFieldSetting(string $setting, mixed $default = null): mixed { return ['amountType' => Payment::VALUE_TYPE_FIXED, 'amountFixed' => '2', 'currency' => 'USD'][$setting] ?? $default; }
    };
    expect($integration->getPaymentAmount(new Submission()))->toBe('3.00')->and($integration->calls)->toBe(1);
    expect($integration->getAmount(new Submission()))->toBe(300)->and($integration->calls)->toBe(2);
})->with(['stripe', 'opayo']);

it('keeps historical short class names separate from class identity and handle overrides', function() {
    expect(HubSpot::className())->toBe('HubSpot')->and(HubSpot::getShortClassName())->toBe('HubSpot')
        ->and(HubSpot::class)->toBe('verbb\\formie\\integrations\\crm\\HubSpot');
    $integration = new class extends HubSpot { public function getClassHandle() { return 'custom'; } };
    expect($integration->getClassHandle())->toBe('custom');
});

it('keeps legacy OAuth transport overrides on the canonical request path', function() {
    $integration = new class extends Crm {
        public function OAuthRequest(string $method = 'GET', string $uri = '', array $options = []) { return ['legacy' => $uri]; }
        public function invokeTransport(): mixed { return $this->requestWithOAuthProvider('GET', 'contacts'); }
    };
    expect($integration->invokeTransport())->toBe(['legacy' => 'contacts']);
});

it('sends a native automation sample without creating a fake submission or retry record', function() {
    $integration = new class(['url' => 'https://8.8.8.8/hook', 'handle' => 'sample']) extends \verbb\formie\integrations\automations\WebRequest {
        public int $requests = 0;
        protected function createDeliveryHttpHandler(): callable {
            return function($request, $options) {
                ++$this->requests;
                return \GuzzleHttp\Promise\Create::promiseFor(new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], '{"accepted":true}'));
            };
        }
    };
    $form = formie()->form()->create();
    $sample = new Submission();
    $sample->setForm($form);
    Formie::$plugin->getSubmissions()->populateFakeSubmission($sample);
    $count = (new \craft\db\Query())->from('{{%formie_delivery_attempts}}')->count();
    $submissionCount = Submission::find()->formId($form->id)->status(null)->count();
    expect($integration->sendTestPayload($sample)->isSuccessful())->toBeTrue()
        ->and($integration->requests)->toBe(1)
        ->and(Submission::find()->formId($form->id)->status(null)->count())->toBe($submissionCount)
        ->and((new \craft\db\Query())->from('{{%formie_delivery_attempts}}')->count())->toBe($count);
});

it('honours legacy Stripe return URL overrides through the explicit creation API', function() {
    $integration = new class extends Stripe {
        public int $calls = 0;
        public function getReturnUrl(Submission $submission): string { ++$this->calls; return 'https://example.test/custom-return'; }
    };
    expect($integration->createPaymentReturnUrl(new Submission()))->toBe('https://example.test/custom-return')
        ->and($integration->calls)->toBe(1);
});

it('retains global refresh parameters for older option-source integrations', function() {
    $integration = new class extends Crm {
        public function getConfigRefreshParams() { return ['legacyRefresh' => true]; }
    };
    expect($integration->getOptionSourceRefreshParams('custom-source'))->toBe(['legacyRefresh' => true]);
});

// Load the actual published automation class, with only the network transport replaced.
$automationGuide = file_get_contents(dirname(__DIR__, 2) . '/docs/guides/integrations/building-an-automation-integration-from-scratch.md');
preg_match('~```php \[modules/formieintegration/src/integrations/ExampleAutomation.php\]\n<\?php\n(.*?)\n```~s', $automationGuide, $automationExample);
eval(str_replace('namespace modules\\formieintegration\\integrations;', 'namespace Tests\\DocumentedAutomation;', $automationExample[1]));

it('executes the documented automation sample with permitted per-form settings', function() {
    $integration = new class(['url' => 'https://8.8.8.8/documented', 'handle' => 'documentedAutomation']) extends Tests\DocumentedAutomation\ExampleAutomation {
        public int $requests = 0;
        protected function createDeliveryHttpHandler(): callable {
            return function($request, $options) {
                ++$this->requests;
                return \GuzzleHttp\Promise\Create::promiseFor(new \GuzzleHttp\Psr7\Response(200, [], '{}'));
            };
        }
    };
    $form = formie()->form()->create();
    $sample = new Submission();
    $sample->setForm($form);
    Formie::$plugin->getSubmissions()->populateFakeSubmission($sample);
    expect($integration->getFormSettingAttributes())->toContain('url', 'method', 'requestType')
        ->and($integration->refreshConfig()->all())->toBe([])
        ->and($integration->requests)->toBe(0)
        ->and($integration->sendTestPayload($sample)->isSuccessful())->toBeTrue()
        ->and($integration->requests)->toBe(1);
});

it('rejects test sends from a user without form integration permission before execution', function() {
    PublicApiAutomationFixture::$writes = 0;
    $integration = new PublicApiAutomationFixture(['name' => 'Restricted action', 'handle' => 'restrictedTest' . bin2hex(random_bytes(5)), 'scope' => Integrations::SCOPE_SITE]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form()->create();
    $suffix = bin2hex(random_bytes(6));
    $actor = new User(['username' => 'noIntegration' . $suffix, 'email' => $suffix . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($actor))->toBeTrue();
    WebRequestTestHelper::withWebRequestContext(function($request) use ($actor, $form, $integration) {
        Craft::$app->getUser()->setIdentity($actor);
        $request->setIsCpRequest(true);
        $request->setBodyParams([$request->csrfParam => $request->getCsrfToken(), 'formId' => $form->id, 'integration' => $integration->handle]);
        expect(fn() => (new IntegrationsController('integrations', Formie::$plugin))->runAction('send-test-payload'))->toThrow(\yii\web\ForbiddenHttpException::class);
        expect(PublicApiAutomationFixture::$writes)->toBe(0);
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);
});

it('uses canonical major-amount overrides when building provider API amounts', function() {
    $integration = new class extends Stripe {
        public function getPaymentAmount(Submission $submission): string|int|float { return '7.50'; }
        public function getCurrency(Submission $submission): ?string { return 'USD'; }
    };
    expect($integration->getProviderAmount(new Submission()))->toBe(750)
        ->and($integration->getAmount(new Submission()))->toBe(750);
    $opayo = new class extends Opayo {
        public function getPaymentAmount(Submission $submission): string|int|float { return '7.50'; }
        public function getCurrency(Submission $submission): ?string { return 'USD'; }
    };
    expect($opayo->getProviderAmount(new Submission()))->toBe(750)
        ->and($opayo->getAmount(new Submission()))->toBe(750);
});


it('preserves optional GraphQL extensions of a native provider through both spellings', function(bool $legacy) {
    $integration = new class extends Stripe {
        public int $calls = 0;
        protected function getOptionalGraphqlPaymentInputFieldKeys(): array {
            ++$this->calls;
            return [...parent::getOptionalGraphqlPaymentInputFieldKeys(), 'customToken'];
        }
        public function optionalKeys(bool $legacy): array {
            return $legacy ? $this->getOptionalGraphqlPaymentInputFieldKeys() : $this->getOptionalGqlPaymentInputFieldKeys();
        }
    };
    expect($integration->optionalKeys($legacy))->toBe(['stripePaymentId', 'stripeSubscriptionId', 'customToken'])
        ->and($integration->calls)->toBe(1);
})->with([false, true]);
