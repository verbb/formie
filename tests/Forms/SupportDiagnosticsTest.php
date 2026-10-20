<?php

declare(strict_types=1);

use craft\elements\User;
use craft\web\View;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\SupportController;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\helpers\SupportDiagnostics;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use verbb\formie\records\Integration as IntegrationRecord;
use verbb\formie\services\Integrations;

beforeEach(function (): void {
    $this->supportTransaction = Craft::$app->getDb()->beginTransaction();
});

afterEach(function (): void {
    $this->supportTransaction->rollBack();
    (new ReflectionMethod(Formie::$plugin->getCaptchaProviders(), '_resetCache'))->invoke(Formie::$plugin->getCaptchaProviders());
    (new ReflectionMethod(Formie::$plugin->getIntegrations(), '_resetIntegrationCaches'))->invoke(Formie::$plugin->getIntegrations());
    Formie::$plugin->getIntegrations()->resetCaptchaCaches();
});

it('reports the sending environment and effective permissions without user details', function (): void {
    $name = 'supportDiagnostics' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, \Tests\Support\PermissionTestHelper::withParents(['accessCp', 'formie-manageCaptchas', 'formie-manageSpamRules'])))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function () use ($user): void {
        Craft::$app->getConfig()->env = 'production';
        Craft::$app->getConfig()->getGeneral()->devMode = false;
        Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        $data = SupportDiagnostics::collect(new Form(['handle' => 'diagnosticForm']));

        expect($data['installation'])->toBe([
            'environment' => 'production',
            'hostname' => 'craft.example.test',
            'phpVersion' => PHP_VERSION,
            'craftVersion' => Craft::$app->getVersion(),
            'formieVersion' => Formie::$plugin->getVersion(),
            'devMode' => false,
            'allowAdminChanges' => false,
        ])->and($data['collectedAt'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/')
            ->and($data['user']['admin'])->toBeFalse()
            ->and($data['user']['canManageForm'])->toBeFalse()
            ->and($data['user']['canManageSpamRules'])->toBeTrue()
            ->and($data['user']['settingsPages'])->not->toHaveKey('general')
            ->and($data['user']['settingsPages']['support'])->toBeFalse()
            ->and(json_encode($data))->not->toContain($user->email, $user->username);
    });
});

it('reports credential availability without literal values or environment variable names', function (?string $value, bool $configured, bool $reference, bool $resolved): void {
    $_SERVER['FORMIE_SUPPORT_TEST_SECRET'] = 'resolved-secret-canary';
    try {
        $captcha = Formie::$plugin->getIntegrations()->getCaptchaByHandle('turnstile');
        $captcha->secretKey = $value;
        $captcha->siteKey = 'public-site-key-canary';
        $captcha->setEnabled(true);
        expect(Formie::$plugin->getIntegrations()->saveCaptcha($captcha))->toBeTrue();

        WebRequestTestHelper::withWebRequestContext(function () use ($configured, $reference, $resolved, $value): void {
            $form = new Form(['handle' => 'diagnosticForm']);
            $form->settings->integrations = ['turnstile' => ['enabled' => true]];
            $data = SupportDiagnostics::collect($form);
            $provider = $data['integrations']['turnstile'];
            expect($provider['status'])->toBe('available')
                ->and($provider['scope'])->toBe(Integrations::SCOPE_SITE)
                ->and($provider['stored'])->toBeTrue()
                ->and($provider['enabledGlobally'])->toBeTrue()
                ->and($provider['enabledForForm'])->toBeTrue()
                ->and($provider['credentials']['secretKey'])->toBe([
                    'configured' => $configured,
                    'usesEnvironmentVariable' => $reference,
                    'resolvesToNonEmptyValue' => $resolved,
                ]);
            $json = json_encode($data);
            expect($json)->not->toContain('resolved-secret-canary', 'public-site-key-canary', 'FORMIE_SUPPORT_TEST_');
            if ($value !== null && strlen($value) > 1) {
                expect($json)->not->toContain($value);
            }
        });
    } finally {
        unset($_SERVER['FORMIE_SUPPORT_TEST_SECRET']);
    }
})->with([
    'literal' => ['literal-secret-canary', true, false, true],
    'zero string' => ['0', true, false, true],
    'nested environment' => ['${FORMIE_SUPPORT_TEST_SECRET}', true, true, true],
    'resolved environment' => ['$FORMIE_SUPPORT_TEST_SECRET', true, true, true],
    'missing environment' => ['$FORMIE_SUPPORT_TEST_MISSING', true, true, false],
    'empty' => ['', false, false, false],
    'unset' => [null, false, false, false],
]);

it('includes referenced missing and disabled integrations without exporting unrelated providers or settings', function (): void {
    foreach (['diagnosticMail', 'unrelatedMail'] as $handle) {
        $record = new IntegrationRecord();
        $record->setAttributes([
            'name' => 'Private account name', 'handle' => $handle, 'type' => Mailchimp::class,
            'scope' => Integrations::SCOPE_PROJECT, 'enabled' => false, 'sortOrder' => 1,
            'settings' => ['apiKey' => 'private-api-key-canary'],
        ], false);
        expect($record->save(false))->toBeTrue();
    }
    (new ReflectionMethod(Formie::$plugin->getIntegrations(), '_resetIntegrationCaches'))->invoke(Formie::$plugin->getIntegrations());

    WebRequestTestHelper::withWebRequestContext(function (): void {
        $form = new Form(['handle' => 'diagnosticForm']);
        $form->settings->integrations = [
            'diagnosticMail' => ['enabled' => true, 'privatePayload' => 'form-secret-canary'],
            'missingProvider' => ['enabled' => true],
        ];
        $data = SupportDiagnostics::collect($form);
        expect($data['integrations']['diagnosticMail']['enabledGlobally'])->toBeFalse()
            ->and($data['integrations']['diagnosticMail']['enabledForForm'])->toBeTrue()
            ->and($data['integrations']['diagnosticMail']['scope'])->toBe('project')
            ->and($data['integrations']['missingProvider'])->toBe(['status' => 'missing', 'enabledForForm' => true])
            ->and($data['integrations'])->not->toHaveKey('unrelatedMail')
            ->and(json_encode($data))->not->toContain('private-api-key-canary', 'Private account name', 'form-secret-canary');
    });
});

it('keeps provider errors and structured credentials out of diagnostics', function (): void {
    $broken = new class(['handle' => 'brokenProvider']) extends Mailchimp {
        public function getEnabled(bool $parse = true): bool|string
        {
            throw new RuntimeException('credential-in-exception-canary');
        }
    };
    $webRequest = new \verbb\formie\integrations\automations\WebRequest([
        'handle' => 'structuredProvider',
        'headers' => [['name' => 'Authorization', 'value' => 'header-secret-canary']],
        'httpAuth' => ['username' => 'private-user-canary', 'password' => 'password-secret-canary'],
    ]);
    $service = Formie::$plugin->getIntegrations();
    (new ReflectionProperty($service, '_integrations'))->setValue($service, new \craft\base\MemoizableArray([$broken, $webRequest]));

    WebRequestTestHelper::withWebRequestContext(function (): void {
        $form = new Form(['handle' => 'diagnosticForm']);
        $form->settings->integrations = ['brokenProvider' => ['enabled' => true], 'structuredProvider' => ['enabled' => true]];
        $data = SupportDiagnostics::collect($form);
        expect($data['integrations']['brokenProvider'])->toBe(['status' => 'unavailable'])
            ->and($data['integrations']['structuredProvider']['credentials']['headers']['configured'])->toBeTrue()
            ->and($data['integrations']['structuredProvider']['credentials']['httpAuth']['resolvesToNonEmptyValue'])->toBeNull()
            ->and(json_encode($data))->not->toContain('credential-in-exception-canary', 'header-secret-canary', 'private-user-canary', 'password-secret-canary');
    });
});

it('attaches diagnostics to the support zip without sending a real request', function (): void {
    $form = formie()->form(['title' => 'Support bundle diagnostic fixture'])->singleLineTextField('message')->create();
    $user = User::find()->admin(true)->one();
    expect($user)->not->toBeNull();
    $history = [];
    $handler = HandlerStack::create(new MockHandler([new Response(200, [], '{}')]));
    $handler->push(Middleware::history($history));
    $definition = Craft::$container->getDefinitions()[Client::class] ?? null;
    Craft::$container->set(Client::class, new Client(['handler' => $handler]));

    try {
        WebRequestTestHelper::withWebRequestContext(function ($request) use ($user, $form): void {
            $request->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity($user);
            $request->setBodyParams([
                $request->csrfParam => $request->getCsrfToken(),
                'fromEmail' => 'support-test@example.test', 'formId' => $form->id, 'message' => 'Diagnostic fixture',
            ]);
            (new SupportController('support', Formie::$plugin))->runAction('send-support-request');
        }, ['method' => 'POST']);
    } finally {
        Craft::$container->clear(Client::class);
        if ($definition !== null) {
            Craft::$container->set(Client::class, $definition);
        }
    }

    expect($history)->toHaveCount(1);
    $payload = json_decode((string)$history[0]['request']->getBody(), true);
    expect($payload)->toHaveKey('fileBody');
    $path = tempnam(sys_get_temp_dir(), 'formie-support-test-');
    try {
        file_put_contents($path, base64_decode($payload['fileBody']));
        $zip = new ZipArchive();
        expect($zip->open($path))->toBeTrue();
        $data = json_decode($zip->getFromName('diagnostics.json'), true);
        expect($data['schemaVersion'])->toBe(3)
            ->and($data['form']['handle'])->toBe($form->handle)
            ->and($data['user']['admin'])->toBeTrue()
            ->and($data['collectionErrors'])->toBe([]);
        $zip->close();
    } finally {
        unlink($path);
    }
});

it('renders the support disclosure and affected-environment guidance', function (): void {
    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        Craft::$app->getView()->registerTwigExtension(new \verbb\base\web\twig\Extension());
        $html = Craft::$app->getView()->renderPageTemplate('formie/settings/support', [
            'settings' => Formie::$plugin->getSettings(),
        ], View::TEMPLATE_MODE_CP);
        expect($html)->toContain('Send this request from the environment where the issue occurs.', 'Installation diagnostics:', 'no credential values or environment-variable names')
            ->not->toContain('nothing else related to your install');
    });
});
