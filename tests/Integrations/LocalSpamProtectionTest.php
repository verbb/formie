<?php

declare(strict_types=1);

use craft\events\ConfigEvent;
use craft\helpers\Json;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\helpers\ProjectConfigHelper;
use verbb\formie\helpers\SpamProtectionEditor;
use verbb\formie\helpers\SpamProtectionSettings as LocalSettings;
use verbb\formie\helpers\Table;
use verbb\formie\services\SpamProtection;
use verbb\formie\integrations\captchas\Turnstile;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

beforeEach(function(): void {
    $this->localSettingsTransaction = Craft::$app->getDb()->beginTransaction();
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = true;
    LocalSettings::resetCaches();
});

afterEach(function(): void {
    $this->localSettingsTransaction->rollBack();
    Craft::$app->getInfo()->configVersion = (new \craft\db\Query())->select('configVersion')->from(\craft\db\Table::INFO)->scalar();
    Craft::$app->getProjectConfig()->reset();
    LocalSettings::resetCaches();
});

function seedLocalTurnstile(string $secret = 'stored-secret'): void
{
    $provider = SpamProtectionEditor::postedProvider('turnstile', ['enabled' => true, 'settings' => ['siteKey' => 'public-site-key', 'secretKey' => $secret]]);
    expect(Formie::$plugin->getCaptchaProviders()->saveProvider($provider))->toBeTrue();
}

it('renders the separate rules and CAPTCHA screens without exposing stored secrets', function(string $action, array $params): void {
    seedLocalTurnstile('must-not-appear-in-html');
    WebRequestTestHelper::withWebRequestContext(function($request) use ($action, $params): void {
        $request->setIsCpRequest(true);
        Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;
        Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin()->one());
        Craft::$app->getView()->registerTwigExtension(new \verbb\base\web\twig\Extension());
        $controller = new \verbb\formie\controllers\SpamProtectionController('spam-protection', Formie::$plugin);
        $response = $controller->runAction($action, $params);
        (new \craft\web\TemplateResponseFormatter())->format($response);
        expect($response->content)->toContain('Spam Protection')->not->toContain('must-not-appear-in-html');
        if ($action === 'captchas') {
            expect($response->content)->not->toContain('disabled inert')->not->toContain('Developer settings')->not->toContain('Clear saved')->toContain('Spam Filtering Services');
        }
    });
})->with([['rules', []], ['captchas', ['handle' => 'turnstile']]]);

it('rolls back a legacy batch without publishing partial project config or completed events', function(): void {
    $before = LocalSettings::record('captcha:turnstile');
    $beforeConfig = Craft::$app->getProjectConfig()->get('formie.captchaProviders.turnstile');
    $events = 0;
    $handler = function() use (&$events) { $events++; };
    $service = Formie::$plugin->getIntegrations();
    $service->on($service::EVENT_AFTER_SAVE_INTEGRATION, $handler);
    try {
        expect(fn() => $service->savePostedCaptchaConfigs([
            'turnstile' => ['enabled' => true, 'settings' => ['siteKey' => 'new-site-key', 'secretKey' => 'new-secret']],
            'hcaptcha' => ['enabled' => true, 'settings' => []],
        ]))->toThrow(BadRequestHttpException::class);
        expect(LocalSettings::record('captcha:turnstile'))->toEqual($before)
            ->and($events)->toBe(0)
            ->and(Craft::$app->getProjectConfig()->get('formie.captchaProviders.turnstile'))->toEqual($beforeConfig);
    } finally {
        $service->off($service::EVENT_AFTER_SAVE_INTEGRATION, $handler);
    }
});

it('allows a CAPTCHA manager to save production credentials independently of rules and other providers', function(): void {
    $name = 'captchaEditor' . bin2hex(random_bytes(5));
    $user = new \craft\elements\User(['username' => $name, 'email' => $name . '@example.test']);
    Craft::$app->getElements()->saveElement($user);
    Craft::$app->getUserPermissions()->saveUserPermissions($user->id, ['accessCp', 'accessPlugin-formie', 'formie-manageCaptchas']);
    $beforeRules = LocalSettings::record(LocalSettings::RULES);
    $beforeOther = LocalSettings::record('captcha:hcaptcha');
    $beforeConfig = Craft::$app->getProjectConfig()->get('formie');
    WebRequestTestHelper::withWebRequestContext(function($request) use ($user): void {
        Craft::$app->getUser()->setIdentity($user);
        $request->setIsCpRequest(true);
        Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'handle' => 'turnstile', 'token' => LocalSettings::token('captcha:turnstile'),
            'provider' => ['enabled' => '1', 'settings' => ['siteKey' => 'production-site-key', 'secretKey' => 'production-only-secret']],
        ]);
        $controller = new \verbb\formie\controllers\SpamProtectionController('spam-protection', Formie::$plugin);
        expect($controller->runAction('save-captcha')->statusCode)->toBe(302);
        expect(fn() => $controller->runAction('rules'))->toThrow(ForbiddenHttpException::class);
        Craft::$app->getConfig()->getGeneral()->allowAdminChanges = true;
        expect($controller->hasMethod('actionChangeLocalSettings'))->toBeFalse();
    }, ['method' => 'POST']);
    expect(LocalSettings::record(LocalSettings::RULES))->toEqual($beforeRules)
        ->and(LocalSettings::record('captcha:hcaptcha'))->toEqual($beforeOther)
        ->and(Craft::$app->getProjectConfig()->get('formie'))->toEqual($beforeConfig)
        ->and(SpamProtectionEditor::provider('turnstile')->secretKey)->toBe('production-only-secret');
});

it('migrates direct and group legacy permissions and continues interpreting old group YAML', function(): void {
    $suffix = bin2hex(random_bytes(5));
    $group = new \craft\models\UserGroup(['name' => 'Spam legacy ' . $suffix, 'handle' => 'spamLegacy' . $suffix]);
    Craft::$app->getUserGroups()->saveGroup($group);
    Craft::$app->getUserPermissions()->saveGroupPermissions($group->id, ['accessCp', 'accessPlugin-formie', 'formie-settingsSpam']);
    $user = new \craft\elements\User(['username' => 'spamGroup' . $suffix, 'email' => 'spamGroup' . $suffix . '@example.test']);
    Craft::$app->getElements()->saveElement($user);
    Craft::$app->getUsers()->assignUserToGroups($user->id, [$group->id]);
    $direct = new \craft\elements\User(['username' => 'spamDirect' . $suffix, 'email' => 'spamDirect' . $suffix . '@example.test']);
    Craft::$app->getElements()->saveElement($direct);
    Craft::$app->getUserPermissions()->saveUserPermissions($direct->id, ['accessCp', 'accessPlugin-formie', 'formie-settingsCaptchas']);
    (new \verbb\formie\migrations\m261020_000000_local_spam_settings())->safeUp();
    foreach ([$user, $direct] as $principal) {
        expect($principal->can('formie-manageCaptchas'))->toBeTrue()->and($principal->can('formie-manageSpamRules'))->toBeTrue();
    }
    Craft::$app->getUserPermissions()->saveGroupPermissions($group->id, ['accessCp', 'accessPlugin-formie', 'formie-settingsSpam']);
    Craft::$app->getUserPermissions()->reset();
    expect(Formie::$plugin->getPermissions()->canManageCaptchas($user))->toBeTrue()
        ->and(Formie::$plugin->getPermissions()->canManageSpamRules($user))->toBeTrue();
});

it('preserves and displays an unavailable provider without exposing its settings', function(): void {
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_CAPTCHA_PROVIDERS, ['type' => 'uninstalled\\Captcha', 'settings' => Json::encode(['secret' => 'unavailable-provider-secret'])], ['handle' => 'turnstile'])->execute();
    LocalSettings::resetCaches();
    WebRequestTestHelper::withWebRequestContext(function($request): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin(true)->one());
        Craft::$app->getView()->registerTwigExtension(new \verbb\base\web\twig\Extension());
        $controller = new \verbb\formie\controllers\SpamProtectionController('spam-protection', Formie::$plugin);
        $response = $controller->runAction('captchas', ['handle' => 'turnstile']);
        (new \craft\web\TemplateResponseFormatter())->format($response);
        expect($response->content)->toContain('Provider unavailable.')->not->toContain('unavailable-provider-secret');
    });
    expect(LocalSettings::record('captcha:turnstile')['type'])->toBe('uninstalled\\Captcha');
});

it('rolls back local settings and defers completed events when a later legacy provider save fails', function(): void {
    $before = LocalSettings::record('captcha:turnstile');
    $beforeConfig = Craft::$app->getProjectConfig()->get('formie.captchaProviders.turnstile');
    $service = Formie::$plugin->getIntegrations();
    $afterEvents = 0;
    $beforeHandler = function($event): void {
        if ($event->integration->handle === 'hcaptcha') {
            throw new RuntimeException('Controlled provider save failure');
        }
    };
    $afterHandler = function() use (&$afterEvents): void { $afterEvents++; };
    $service->on($service::EVENT_BEFORE_SAVE_INTEGRATION, $beforeHandler);
    $service->on($service::EVENT_AFTER_SAVE_INTEGRATION, $afterHandler);
    try {
        expect(fn() => $service->savePostedCaptchaConfigs([
            'turnstile' => ['enabled' => true, 'settings' => ['siteKey' => 'new-key', 'secretKey' => 'new-secret']],
            'hcaptcha' => ['enabled' => true, 'settings' => ['siteKey' => 'second-key', 'secretKey' => 'second-secret']],
        ]))->toThrow(RuntimeException::class, 'Controlled provider save failure');
        expect(LocalSettings::record('captcha:turnstile'))->toEqual($before)
            ->and(Craft::$app->getProjectConfig()->get('formie.captchaProviders.turnstile'))->toEqual($beforeConfig)
            ->and($afterEvents)->toBe(0);
    } finally {
        $service->off($service::EVENT_BEFORE_SAVE_INTEGRATION, $beforeHandler);
        $service->off($service::EVENT_AFTER_SAVE_INTEGRATION, $afterHandler);
    }
});

it('rejects a mixed legacy save in full when the user can manage only spam rules', function(): void {
    $name = 'ruleEditor' . bin2hex(random_bytes(5));
    $user = new \craft\elements\User(['username' => $name, 'email' => $name . '@example.test']);
    Craft::$app->getElements()->saveElement($user);
    Craft::$app->getUserPermissions()->saveUserPermissions($user->id, ['accessCp', 'accessPlugin-formie', 'formie-manageSpamRules']);
    $beforeRules = LocalSettings::record(LocalSettings::RULES);
    $beforeProvider = LocalSettings::record('captcha:turnstile');
    WebRequestTestHelper::withWebRequestContext(function($request) use ($user): void {
        Craft::$app->getUser()->setIdentity($user);
        $request->setIsCpRequest(true);
        Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'page' => 'spam-protection', 'token' => LocalSettings::token(LocalSettings::RULES),
            'settings' => ['spamKeywords' => 'must-not-be-saved'],
            'integrations' => ['turnstile' => ['token' => LocalSettings::token('captcha:turnstile'), 'enabled' => false]],
        ]);
        $controller = new \verbb\formie\controllers\SettingsController('settings', Formie::$plugin);
        expect(fn() => $controller->runAction('save-settings'))->toThrow(ForbiddenHttpException::class);
    }, ['method' => 'POST']);
    expect(LocalSettings::record(LocalSettings::RULES))->toEqual($beforeRules)
        ->and(LocalSettings::record('captcha:turnstile'))->toEqual($beforeProvider);
});


it('explains disabled and missing CAPTCHA bindings without requiring credential management', function(): void {
    $form = new \verbb\formie\elements\Form();
    $form->settings->integrations = ['turnstile' => ['enabled' => true]];
    expect(Json::encode(SpamProtectionEditor::formWarnings($form)))->toContain('Disabled');
    $provider = SpamProtectionEditor::postedProvider('turnstile', ['enabled' => true, 'settings' => ['siteKey' => 'public', 'secretKey' => 'saved-secret']]);
    expect(Formie::$plugin->getCaptchaProviders()->saveProvider($provider))->toBeTrue();
    expect(SpamProtectionEditor::formWarnings($form))->toBe([]);
    $provider->secretKey = '$FORMIE_TEST_MISSING_CAPTCHA_SECRET';
    expect(Formie::$plugin->getCaptchaProviders()->saveProvider($provider))->toBeTrue();
    expect(Json::encode(SpamProtectionEditor::formWarnings($form)))->toContain('environment variable')->not->toContain('$FORMIE_TEST_MISSING_CAPTCHA_SECRET');
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_CAPTCHA_PROVIDERS, ['type' => 'uninstalled\\Captcha'], ['handle' => 'turnstile'])->execute();
    LocalSettings::resetCaches();
    expect(Json::encode(SpamProtectionEditor::formWarnings($form)))->toContain('Provider unavailable')->not->toContain('saved-secret');
});


it('allows a spam-rule manager to save production rules without CAPTCHA access', function(): void {
    $name = 'spamEditor' . bin2hex(random_bytes(5));
    $user = new \craft\elements\User(['username' => $name, 'email' => $name . '@example.test']);
    Craft::$app->getElements()->saveElement($user);
    Craft::$app->getUserPermissions()->saveUserPermissions($user->id, ['accessCp', 'accessPlugin-formie', 'formie-manageSpamRules']);
    $before = LocalSettings::record('captcha:turnstile');
    $config = Craft::$app->getProjectConfig()->get('formie');
    WebRequestTestHelper::withWebRequestContext(function($request) use ($user): void {
        Craft::$app->getUser()->setIdentity($user);
        Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;
        $request->setIsCpRequest(true);
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'token' => LocalSettings::token(LocalSettings::RULES),
            'settings' => ['spamKeywords' => 'client-managed-rule'],
        ]);
        $controller = new \verbb\formie\controllers\SpamProtectionController('spam-protection', Formie::$plugin);
        expect($controller->runAction('save-rules')->statusCode)->toBe(302);
        expect(fn() => $controller->runAction('captchas'))->toThrow(ForbiddenHttpException::class);
    }, ['method' => 'POST']);
    expect(Formie::$plugin->getSpamProtection()->getSettingsValues()['spamKeywords'])->toBe('client-managed-rule')
        ->and(LocalSettings::record('captcha:turnstile'))->toEqual($before)
        ->and(Craft::$app->getProjectConfig()->get('formie'))->toEqual($config);
});

it('retains unregistered legacy provider configuration during the migration seed', function(): void {
    Formie::$plugin->getCaptchaProviders()->seedRegistryFromLegacySettings([
        'customLegacyCaptcha' => ['type' => 'uninstalled\\CustomCaptcha', 'enabled' => '$CUSTOM_CAPTCHA_ENABLED', 'settings' => ['secretKey' => '$CUSTOM_CAPTCHA_SECRET']],
    ]);
    (new \verbb\formie\migrations\m261020_000000_local_spam_settings())->safeUp();
    $row = LocalSettings::record('captcha:customLegacyCaptcha');
    expect($row['type'])->toBe('uninstalled\\CustomCaptcha')
        ->and($row['enabled'])->toBe('$CUSTOM_CAPTCHA_ENABLED')
        ->and(Json::decode($row['settings']))->toBe(['secretKey' => '$CUSTOM_CAPTCHA_SECRET'])
        ->and($row['scope'])->toBe('site');
});

it('migrates both former scopes without changing effective values and can run twice', function(string $scope): void {
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SPAM_SETTINGS, ['scope' => $scope, 'spamKeywords' => 'production-rule', 'spamLimit' => 27, 'saveSpam' => false, 'dateUpdated' => '2020-01-02 03:04:05'])->execute();
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_CAPTCHA_PROVIDERS, ['scope' => $scope, 'enabled' => '$LOCAL_ENABLED', 'settings' => Json::encode(['siteKey' => 'production-site', 'secretKey' => '$LOCAL_SECRET']), 'dateUpdated' => '2020-01-02 03:04:05'], ['handle' => 'turnstile'])->execute();
    $rules = LocalSettings::record(LocalSettings::RULES);
    $provider = LocalSettings::record('captcha:turnstile');
    $config = Craft::$app->getProjectConfig()->get('formie');
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;
    $migration = new \verbb\formie\migrations\m261020_000000_local_spam_settings();
    $migration->safeUp();
    $migration->safeUp();
    expect(LocalSettings::record(LocalSettings::RULES))->toEqual([...$rules, 'scope' => 'site'])
        ->and(LocalSettings::record('captcha:turnstile'))->toEqual([...$provider, 'scope' => 'site'])
        ->and(Craft::$app->getProjectConfig()->get('formie'))->toEqual($config);
})->with(['project', 'site']);

it('ignores actual project config add update removal and rebuild for both stores', function(): void {
    seedLocalTurnstile('production-secret');
    $rules = LocalSettings::record(LocalSettings::RULES);
    $provider = LocalSettings::record('captcha:turnstile');
    $config = Craft::$app->getProjectConfig();
    foreach (['first-deployment', 'second-deployment'] as $value) {
        $config->set('formie.captchaProviders.turnstile', ['type' => Turnstile::class, 'enabled' => false, 'settings' => ['secretKey' => $value]]);
        $config->set('formie.spamSettings', ['spamKeywords' => $value, 'saveSpam' => false]);
    }
    $config->remove('formie.captchaProviders.turnstile');
    $config->remove('formie.spamSettings');
    expect(LocalSettings::record(LocalSettings::RULES))->toEqual($rules)
        ->and(LocalSettings::record('captcha:turnstile'))->toEqual($provider)
        ->and(ProjectConfigHelper::rebuildProjectConfig())->not->toHaveKey('captchaProviders')->not->toHaveKey('spamSettings');
});

it('seeds legacy values locally and never replaces them with a subsequent seed', function(): void {
    Craft::$app->getDb()->createCommand()->delete(Table::FORMIE_CAPTCHA_PROVIDERS)->execute();
    LocalSettings::resetCaches();
    $service = Formie::$plugin->getCaptchaProviders();
    $service->seedRegistryFromLegacySettings(['turnstile' => ['enabled' => '$LOCAL_ENABLED', 'settings' => ['siteKey' => 'public', 'secretKey' => '$LOCAL_SECRET']]]);
    $before = LocalSettings::record('captcha:turnstile');
    $service->seedRegistryFromLegacySettings(['turnstile' => ['enabled' => false, 'settings' => []]]);
    expect(LocalSettings::record('captcha:turnstile'))->toEqual($before)
        ->and($before['scope'])->toBe('site')
        ->and($before['enabled'])->toBe('$LOCAL_ENABLED')
        ->and(Json::decode($before['settings'])['secretKey'])->toBe('$LOCAL_SECRET')
        ->and(LocalSettings::record('captcha:hcaptcha')['enabled'])->toBe('false');
});

it('retains masked credentials replaces them and clears only explicitly emptied fields', function(): void {
    seedLocalTurnstile('original-secret');
    $provider = SpamProtectionEditor::provider('turnstile');
    SpamProtectionEditor::mask($provider);
    expect($provider->secretKey)->toBe(SpamProtectionEditor::SECRET_PLACEHOLDER);
    foreach ([[], ['secretKey' => SpamProtectionEditor::SECRET_PLACEHOLDER]] as $settings) {
        $posted = SpamProtectionEditor::postedProvider('turnstile', ['settings' => $settings], clearEmptySecrets: true);
        expect($posted->secretKey)->toBe('original-secret');
    }
    $replacement = SpamProtectionEditor::postedProvider('turnstile', ['settings' => ['secretKey' => 'replacement']], clearEmptySecrets: true);
    expect($replacement->secretKey)->toBe('replacement');
    // Clearing an enabled provider must fail validation without changing the saved key.
    expect(fn() => SpamProtectionEditor::postedProvider('turnstile', ['settings' => ['secretKey' => '']], clearEmptySecrets: true))->toThrow(BadRequestHttpException::class);
    expect(SpamProtectionEditor::provider('turnstile')->secretKey)->toBe('original-secret');
    $cleared = SpamProtectionEditor::postedProvider('turnstile', ['enabled' => false, 'settings' => ['secretKey' => '']], clearEmptySecrets: true);
    expect(Formie::$plugin->getCaptchaProviders()->saveProvider($cleared))->toBeTrue();
    expect(SpamProtectionEditor::provider('turnstile')->secretKey)->toBeNull();
    seedLocalTurnstile('$UNRESOLVED_LOCAL_SECRET');
    $reference = SpamProtectionEditor::provider('turnstile');
    SpamProtectionEditor::mask($reference);
    expect($reference->secretKey)->toBe('$UNRESOLVED_LOCAL_SECRET');
});

it('rejects stale local edits without overwriting a newer save', function(): void {
    $token = LocalSettings::token('captcha:turnstile');
    seedLocalTurnstile('newer-secret');
    expect(fn() => LocalSettings::assertCurrent('captcha:turnstile', $token))->toThrow(BadRequestHttpException::class);
    expect(SpamProtectionEditor::provider('turnstile')->secretKey)->toBe('newer-secret');
});
