<?php

use craft\db\Query;
use craft\helpers\Json;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\helpers\SpamProtectionSettings;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261024_000000_retired_captchas;
use verbb\formie\models\FormSettings;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;

beforeEach(function(): void {
    // MySQL index DDL commits transactions. Prepare the legacy migration's index
    // before opening the transaction that isolates its provider settings.
    (new \verbb\formie\migrations\m260618_000000_captcha_providers())->createIndexIfMissing(Table::FORMIE_CAPTCHA_PROVIDERS, ['handle'], true);
    $this->retiredCaptchaTransaction = Craft::$app->getDb()->beginTransaction();
    SpamProtectionSettings::resetCaches();
});

afterEach(function(): void {
    $this->retiredCaptchaTransaction->rollBack();
    SpamProtectionSettings::resetCaches();
    Formie::$plugin->getForms()->invalidateFormCaches();
});

function retiredCaptchaRow(string $handle, string $type): void
{
    Craft::$app->getDb()->createCommand()->insert(Table::FORMIE_CAPTCHA_PROVIDERS, [
        'handle' => $handle, 'type' => $type, 'scope' => 'site', 'enabled' => 'true',
        'settings' => Json::encode(['secret' => 'keep-me']),
        'dateCreated' => '2020-01-01 00:00:00', 'dateUpdated' => '2020-01-01 00:00:00',
        'uid' => \craft\helpers\StringHelper::UUID(),
    ])->execute();
}

it('skips retired built-ins while retaining unknown providers and explicit handle collisions during seeding', function(bool $includeTypes): void {
    $legacy = [
        'duplicate' => ['enabled' => true],
        'honeypot' => ['enabled' => false],
        'javascript' => ['enabled' => '$JS_ENABLED'],
        'missingCustom' => ['type' => 'uninstalled\\Captcha', 'enabled' => '$CUSTOM_ENABLED', 'settings' => ['secret' => '$CUSTOM_SECRET']],
    ];
    if ($includeTypes) {
        foreach (['duplicate' => 'Duplicate', 'honeypot' => 'Honeypot', 'javascript' => 'Javascript'] as $handle => $class) {
            $legacy[$handle]['type'] = 'verbb\\formie\\integrations\\captchas\\' . $class;
        }
    }
    $service = Formie::$plugin->getCaptchaProviders();
    $service->seedRegistryFromLegacySettings($legacy);
    foreach (['duplicate', 'honeypot', 'javascript'] as $handle) {
        expect($service->getProviderRowByHandle($handle))->toBeNull();
    }
    expect($service->getProviderRowByHandle('missingCustom')['settings'])->toBe(['secret' => '$CUSTOM_SECRET']);
    $service->seedRegistryFromLegacySettings(['duplicate' => ['type' => 'custom\\Duplicate', 'enabled' => true]]);
    expect($service->getProviderRowByHandle('duplicate')['type'])->toBe('custom\\Duplicate');
})->with([true, false]);

it('runs the legacy upgrade without seeding retired providers or changing existing guards', function(): void {
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;
    Formie::$plugin->getSettings()->captchas = [
        'duplicate' => ['type' => 'verbb\\formie\\integrations\\captchas\\Duplicate', 'enabled' => true],
        'honeypot' => ['type' => 'verbb\\formie\\integrations\\captchas\\Honeypot', 'enabled' => true],
        'javascript' => ['type' => 'verbb\\formie\\integrations\\captchas\\Javascript', 'enabled' => true, 'settings' => ['minTime' => 99]],
        'missingCustom' => ['type' => 'uninstalled\\Captcha', 'enabled' => '$CUSTOM_ENABLED', 'settings' => ['secret' => '$CUSTOM_SECRET']],
    ];
    $guardsBefore = SpamProtectionSettings::record(SpamProtectionSettings::RULES);
    expect((new \verbb\formie\migrations\m260618_000000_captcha_providers())->safeUp())->toBeTrue();
    expect((new m261024_000000_retired_captchas())->safeUp())->toBeTrue();
    foreach (['duplicate', 'honeypot', 'javascript'] as $handle) {
        expect(Formie::$plugin->getCaptchaProviders()->getProviderRowByHandle($handle))->toBeNull();
    }
    expect(Formie::$plugin->getSettings()->captchas)->toBe([])
        ->and(Formie::$plugin->getCaptchaProviders()->getProviderRowByHandle('missingCustom')['settings'])->toBe(['secret' => '$CUSTOM_SECRET'])
        ->and(SpamProtectionSettings::record(SpamProtectionSettings::RULES))->toBe($guardsBefore);
});

it('repairs stored providers forms and stencils without changing guards or third-party data', function(bool $allowAdminChanges, bool $includeTypes): void {
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = $allowAdminChanges;
    $bindings = ['missingCustom' => ['enabled' => true, 'setting' => 'preserved']];
    foreach (['duplicate' => 'Duplicate', 'honeypot' => 'Honeypot', 'javascript' => 'Javascript'] as $handle => $class) {
        retiredCaptchaRow($handle, $includeTypes ? 'verbb\\formie\\integrations\\captchas\\' . $class : '');
        $bindings[$handle] = ['enabled' => true];
    }
    retiredCaptchaRow('missingCustom', 'uninstalled\\Captcha');
    SpamProtectionSettings::resetCaches();
    $customBefore = SpamProtectionSettings::record('captcha:missingCustom');
    $activeBefore = SpamProtectionSettings::record('captcha:turnstile');
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SPAM_SETTINGS, [
        'enableHoneypot' => false, 'honeypotFieldName' => 'existingTrap',
        'enableMinimumSubmitTime' => true, 'minimumSubmitTime' => 11, 'enableReplayProtection' => false,
    ])->execute();
    $guardsBefore = SpamProtectionSettings::record(SpamProtectionSettings::RULES);
    $form = formie()->form()->create();
    $settings = ['integrations' => $bindings, 'submitButtonLabel' => 'Keep this', 'custom' => ['value' => 0]];
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORMS, ['settings' => Json::encode($settings)], ['id' => $form->id])->execute();
    $stencil = new Stencil(['name' => 'Retired CAPTCHA fixture', 'handle' => 'retiredCaptcha' . bin2hex(random_bytes(4)), 'scope' => 'site', 'data' => new StencilData()]);
    expect(Formie::$plugin->getStencils()->saveStencil($stencil))->toBeTrue();
    $data = ['settings' => $settings, 'pages' => [], 'custom' => ['keep' => true]];
    $encodedData = Json::encode($data);
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_STENCILS, ['data' => $includeTypes ? $encodedData : Json::encode($encodedData)], ['id' => $stencil->id])->execute();
    $projectConfigBefore = Craft::$app->getProjectConfig()->get('formie');

    // Populate caches before repairing an installation which already ran the old migration.
    expect(Formie::$plugin->getCaptchaProviders()->getProviderRowByHandle('duplicate'))->not->toBeNull();
    $migration = new m261024_000000_retired_captchas();
    expect($migration->safeUp())->toBeTrue();
    expect($migration->safeUp())->toBeTrue();
    foreach (['duplicate', 'honeypot', 'javascript'] as $handle) {
        expect(Formie::$plugin->getCaptchaProviders()->getProviderRowByHandle($handle))->toBeNull();
        unset($settings['integrations'][$handle]);
    }
    $data['settings'] = $settings;
    expect(Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar()))->toBe($settings)
        ->and(Json::decode((new Query())->select('data')->from(Table::FORMIE_STENCILS)->where(['id' => $stencil->id])->scalar()))->toBe($data)
        ->and(SpamProtectionSettings::record('captcha:missingCustom'))->toBe($customBefore)
        ->and(SpamProtectionSettings::record('captcha:turnstile'))->toBe($activeBefore)
        ->and(SpamProtectionSettings::record(SpamProtectionSettings::RULES))->toBe($guardsBefore)
        ->and(Craft::$app->getProjectConfig()->get('formie'))->toBe($projectConfigBefore);

    WebRequestTestHelper::withWebRequestContext(function($request): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin()->one());
        Craft::$app->getView()->registerTwigExtension(new \verbb\base\web\twig\Extension());
        $controller = new \verbb\formie\controllers\SpamProtectionController('spam-protection', Formie::$plugin);
        $response = $controller->runAction('captchas');
        (new \craft\web\TemplateResponseFormatter())->format($response);
        expect($response->content)->not->toContain('duplicate (Unavailable)')->not->toContain('honeypot (Unavailable)')->not->toContain('javascript (Unavailable)')->toContain('missingCustom (Unavailable)');
    });
})->with([true, false])->with([true, false]);

it('retains custom providers and their untyped form bindings when they reuse retired handles', function(string $handle): void {
    retiredCaptchaRow($handle, 'uninstalled\\CustomCaptcha');
    SpamProtectionSettings::resetCaches();
    $before = SpamProtectionSettings::record('captcha:' . $handle);
    $bindings = [$handle => ['enabled' => true, 'custom' => 'keep']];
    $settings = new FormSettings(['integrations' => $bindings]);
    expect($settings->integrations)->toBe($bindings);
    $settings->setAttributes(['integrations' => $bindings], false);
    expect($settings->integrations)->toBe($bindings);
    $form = formie()->form()->create();
    $storedSettings = Json::encode(['integrations' => $bindings]);
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORMS, ['settings' => $storedSettings], ['id' => $form->id])->execute();
    (new m261024_000000_retired_captchas())->safeUp();
    expect(SpamProtectionSettings::record('captcha:' . $handle))->toBe($before)
        ->and((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar())->toBe($storedSettings);
})->with(['duplicate', 'honeypot', 'javascript']);
