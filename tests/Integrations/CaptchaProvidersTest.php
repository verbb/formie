<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\captchas\Hcaptcha;
use verbb\formie\integrations\captchas\Turnstile;
use verbb\formie\services\Integrations;

it('seeds one captcha provider row per registry handle', function (): void {
    $handles = [];

    foreach (Formie::$plugin->getIntegrations()->getIntegrationTypes(\verbb\formie\base\Integration::TYPE_CAPTCHA) as $captchaClass) {
        $handles[] = (new $captchaClass())->getHandle();
    }

    $rows = (new \craft\db\Query())
        ->select(['handle'])
        ->from([Table::FORMIE_CAPTCHA_PROVIDERS])
        ->column();

    expect($rows)->toHaveCount(count($handles));

    foreach ($handles as $handle) {
        expect($rows)->toContain($handle);
    }
});

it('loads captcha providers from the database store', function (): void {
    $turnstile = Formie::$plugin->getIntegrations()->getCaptchaByHandle('turnstile');

    expect($turnstile)->not->toBeNull()
        ->and($turnstile)->toBeInstanceOf(Turnstile::class);
});

it('saves site-scoped captcha providers when admin changes are disabled', function (): void {
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;

    $captcha = Formie::$plugin->getIntegrations()->getCaptchaByHandle('hcaptcha');
    $captcha->scope = Integrations::SCOPE_SITE;
    $captcha->setEnabled(true);
    $captcha->siteKey = 'test-site-key';
    $captcha->secretKey = 'test-secret-key';

    expect(Formie::$plugin->getIntegrations()->saveCaptcha($captcha))->toBeTrue();

    $reloaded = Formie::$plugin->getIntegrations()->getCaptchaByHandle('hcaptcha');

    expect($reloaded)->toBeInstanceOf(Hcaptcha::class)
        ->and($reloaded->isSiteScope())->toBeTrue()
        ->and($reloaded->getEnabled())->toBeTrue()
        ->and($reloaded->siteKey)->toBe('test-site-key');
});

it('blocks project-scoped captcha saves when admin changes are disabled', function (): void {
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;

    $turnstile = Formie::$plugin->getIntegrations()->getCaptchaByHandle('turnstile');
    $turnstile->scope = Integrations::SCOPE_PROJECT;
    $turnstile->setEnabled(true);

    expect(Formie::$plugin->getIntegrations()->saveCaptcha($turnstile))->toBeFalse()
        ->and($turnstile->hasErrors())->toBeTrue();
});

it('saves project-scoped captcha providers to project config when admin changes are enabled', function (): void {
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = true;

    $turnstile = Formie::$plugin->getIntegrations()->getCaptchaByHandle('turnstile');
    $turnstile->scope = Integrations::SCOPE_PROJECT;
    $turnstile->setEnabled(true);
    $turnstile->siteKey = 'project-site-key';
    $turnstile->secretKey = 'project-secret-key';

    expect(Formie::$plugin->getIntegrations()->saveCaptcha($turnstile))->toBeTrue();

    $reloaded = Formie::$plugin->getIntegrations()->getCaptchaByHandle('turnstile');

    expect($reloaded->isProjectScope())->toBeTrue()
        ->and($reloaded->siteKey)->toBe('project-site-key')
        ->and($reloaded->secretKey)->toBe('project-secret-key');

    $config = \verbb\formie\helpers\ProjectConfigHelper::rebuildProjectConfig();

    expect($config['captchaProviders']['turnstile']['settings']['siteKey'] ?? null)->toBe('project-site-key');
});

it('exports only project-scoped captcha providers in project config rebuild data', function (): void {
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;

    $hcaptcha = Formie::$plugin->getIntegrations()->getCaptchaByHandle('hcaptcha');
    $hcaptcha->scope = Integrations::SCOPE_SITE;
    $hcaptcha->setEnabled(true);
    Formie::$plugin->getIntegrations()->saveCaptcha($hcaptcha);

    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = true;

    $turnstile = Formie::$plugin->getIntegrations()->getCaptchaByHandle('turnstile');
    $turnstile->scope = Integrations::SCOPE_PROJECT;
    $turnstile->setEnabled(false);
    Formie::$plugin->getIntegrations()->saveCaptcha($turnstile);

    $config = \verbb\formie\helpers\ProjectConfigHelper::rebuildProjectConfig();

    expect($config['captchaProviders'] ?? null)->toBeArray()
        ->and($config['captchaProviders'])->toHaveKey('turnstile')
        ->and($config['captchaProviders'])->not->toHaveKey('hcaptcha');
});

it('strips captcha keys from plugin settings payloads', function (): void {
    $stripped = Formie::$plugin->getCaptchaProviders()->stripFromPluginSettingsArray([
        'pluginName' => 'Formie',
        'captchas' => ['turnstile' => ['enabled' => true]],
    ]);

    expect($stripped)->toBe(['pluginName' => 'Formie'])
        ->and($stripped)->not->toHaveKey('captchas');
});
