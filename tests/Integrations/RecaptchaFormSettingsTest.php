<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\integrations\captchas\Recaptcha;
use verbb\formie\models\ClientModuleContext;
use verbb\formie\services\Integrations;

function configureRecaptchaProvider(callable $configure): Recaptcha
{
    $recaptcha = Formie::$plugin->getIntegrations()->getCaptchaByHandle('recaptcha');
    expect($recaptcha)->toBeInstanceOf(Recaptcha::class);

    $configure($recaptcha);

    expect(Formie::$plugin->getIntegrations()->saveCaptcha($recaptcha))->toBeTrue();

    return Formie::$plugin->getIntegrations()->getCaptchaByHandle('recaptcha');
}

function invokeRecaptchaMethod(Recaptcha $recaptcha, string $method): mixed
{
    $reflection = new ReflectionClass($recaptcha);
    $reflectionMethod = $reflection->getMethod($method);
    $reflectionMethod->setAccessible(true);

    return $reflectionMethod->invoke($recaptcha);
}

function createRecaptchaForm(array $integrationSettings): Form
{
    $form = new Form();
    $form->handle = 'recaptcha-form-' . uniqid();
    $form->title = 'reCAPTCHA Form Settings Test';
    $form->settings->integrations = [
        'recaptcha' => array_merge([
            'enabled' => true,
        ], $integrationSettings),
    ];

    return $form;
}

it('does not expose conditional logic settings in captcha form settings', function (): void {
    configureRecaptchaProvider(function (Recaptcha $recaptcha): void {
        $recaptcha->scope = Integrations::SCOPE_SITE;
        $recaptcha->setEnabled(true);
        $recaptcha->siteKey = 'test-site-key';
        $recaptcha->secretKey = 'test-secret-key';
        $recaptcha->type = Recaptcha::RECAPTCHA_TYPE_V3;
    });

    $recaptcha = Formie::$plugin->getIntegrations()->getCaptchaByHandle('recaptcha');
    $schema = $recaptcha->getFormSettingsSchema(createRecaptchaForm([]));
    $fieldNames = array_map(static fn(array $field) => $field['name'] ?? null, $schema);

    expect($fieldNames)->toContain('enabled')
        ->and($fieldNames)->toContain('showAllPages')
        ->and($fieldNames)->not->toContain('enableConditions')
        ->and($fieldNames)->not->toContain('conditions');
});

it('exposes form-level action and minimum score fields for score-based reCAPTCHA types', function (): void {
    configureRecaptchaProvider(function (Recaptcha $recaptcha): void {
        $recaptcha->scope = Integrations::SCOPE_SITE;
        $recaptcha->setEnabled(true);
        $recaptcha->siteKey = 'test-site-key';
        $recaptcha->secretKey = 'test-secret-key';
        $recaptcha->type = Recaptcha::RECAPTCHA_TYPE_V3;
        $recaptcha->action = 'global-submit';
        $recaptcha->minScore = 0.5;
    });

    $form = createRecaptchaForm([]);
    $config = Formie::$plugin->getIntegrations()->getIntegrationFormSettingsConfig('recaptcha', $form);

    expect($config)->not->toBeNull();

    $fieldPaths = array_column($config['schemaIndex']['fieldEntries'], 'path');

    expect($fieldPaths)->toContain('formAction')
        ->and($fieldPaths)->toContain('formMinScore');
});

it('hides form-level minimum score for enterprise policy keys', function (): void {
    configureRecaptchaProvider(function (Recaptcha $recaptcha): void {
        $recaptcha->scope = Integrations::SCOPE_SITE;
        $recaptcha->setEnabled(true);
        $recaptcha->siteKey = 'test-site-key';
        $recaptcha->secretKey = 'test-secret-key';
        $recaptcha->projectId = 'test-project';
        $recaptcha->type = Recaptcha::RECAPTCHA_TYPE_ENTERPRISE;
        $recaptcha->enterpriseType = Recaptcha::ENTERPRISE_MODE_POLICY;
    });

    $form = createRecaptchaForm([]);
    $config = Formie::$plugin->getIntegrations()->getIntegrationFormSettingsConfig('recaptcha', $form);
    $fieldPaths = array_column($config['schemaIndex']['fieldEntries'], 'path');

    expect($fieldPaths)->toContain('formAction')
        ->and($fieldPaths)->not->toContain('formMinScore');
});

it('resolves form-level reCAPTCHA action and minimum score overrides for enabled forms', function (): void {
    configureRecaptchaProvider(function (Recaptcha $recaptcha): void {
        $recaptcha->scope = Integrations::SCOPE_SITE;
        $recaptcha->setEnabled(true);
        $recaptcha->siteKey = 'test-site-key';
        $recaptcha->secretKey = 'test-secret-key';
        $recaptcha->type = Recaptcha::RECAPTCHA_TYPE_V3;
        $recaptcha->action = 'global-submit';
        $recaptcha->minScore = 0.5;
    });

    $form = createRecaptchaForm([
        'formAction' => 'contact-form',
        'formMinScore' => '0.8',
    ]);

    $enabledCaptchas = Formie::$plugin->getIntegrations()->getAllEnabledCaptchasForForm($form);
    $recaptcha = collect($enabledCaptchas)->first(fn($captcha) => $captcha->handle === 'recaptcha');

    expect($recaptcha)->toBeInstanceOf(Recaptcha::class)
        ->and(invokeRecaptchaMethod($recaptcha, '_getRecaptchaAction'))->toBe('contact-form')
        ->and(invokeRecaptchaMethod($recaptcha, '_getMinScore'))->toBe(0.8);

    $module = $recaptcha->getClientModule(new ClientModuleContext([
        'form' => $form,
    ]));

    expect($module)->not->toBeNull()
        ->and($module->config['action'] ?? null)->toBe('contact-form');
});

it('inherits global reCAPTCHA action and minimum score when form overrides are blank', function (): void {
    configureRecaptchaProvider(function (Recaptcha $recaptcha): void {
        $recaptcha->scope = Integrations::SCOPE_SITE;
        $recaptcha->setEnabled(true);
        $recaptcha->siteKey = 'test-site-key';
        $recaptcha->secretKey = 'test-secret-key';
        $recaptcha->type = Recaptcha::RECAPTCHA_TYPE_V3;
        $recaptcha->action = 'global-submit';
        $recaptcha->minScore = 0.6;
    });

    $form = createRecaptchaForm([
        'formAction' => '',
        'formMinScore' => '',
    ]);

    $enabledCaptchas = Formie::$plugin->getIntegrations()->getAllEnabledCaptchasForForm($form);
    $recaptcha = collect($enabledCaptchas)->first(fn($captcha) => $captcha->handle === 'recaptcha');

    expect($recaptcha)->toBeInstanceOf(Recaptcha::class)
        ->and(invokeRecaptchaMethod($recaptcha, '_getRecaptchaAction'))->toBe('global-submit')
        ->and(invokeRecaptchaMethod($recaptcha, '_getMinScore'))->toBe(0.6);
});

it('merges posted captcha settings with existing provider credentials when saving', function (): void {
    configureRecaptchaProvider(function (Recaptcha $recaptcha): void {
        $recaptcha->scope = Integrations::SCOPE_SITE;
        $recaptcha->setEnabled(false);
        $recaptcha->siteKey = 'existing-site-key';
        $recaptcha->secretKey = 'existing-secret-key';
        $recaptcha->type = Recaptcha::RECAPTCHA_TYPE_V3;
    });

    $integration = Formie::$plugin->getIntegrations()->buildCaptchaFromPostedConfig('recaptcha', [
        'type' => Recaptcha::class,
        'enabled' => true,
        'settings' => [
            'type' => Recaptcha::RECAPTCHA_TYPE_ENTERPRISE,
            'projectId' => 'test-project',
        ],
    ]);

    expect($integration)->toBeInstanceOf(Recaptcha::class)
        ->and($integration->getEnabled())->toBeTrue()
        ->and($integration->siteKey)->toBe('existing-site-key')
        ->and($integration->secretKey)->toBe('existing-secret-key')
        ->and($integration->type)->toBe(Recaptcha::RECAPTCHA_TYPE_ENTERPRISE)
        ->and($integration->projectId)->toBe('test-project');

    expect(Formie::$plugin->getIntegrations()->savePostedCaptchaConfigs([
        'recaptcha' => [
            'type' => Recaptcha::class,
            'enabled' => true,
            'settings' => [
                'type' => Recaptcha::RECAPTCHA_TYPE_ENTERPRISE,
                'projectId' => 'test-project',
            ],
        ],
    ]))->toBeTrue();

    $reloaded = Formie::$plugin->getIntegrations()->getCaptchaByHandle('recaptcha');

    expect($reloaded)->toBeInstanceOf(Recaptcha::class)
        ->and($reloaded->getEnabled())->toBeTrue()
        ->and($reloaded->siteKey)->toBe('existing-site-key')
        ->and($reloaded->secretKey)->toBe('existing-secret-key');
});

it('fails captcha saves when no captcha settings were posted', function (): void {
    expect(Formie::$plugin->getIntegrations()->savePostedCaptchaConfigs(null))->toBeFalse()
        ->and(Formie::$plugin->getIntegrations()->savePostedCaptchaConfigs([]))->toBeFalse();
});

it('enables recaptcha from posted spam protection captcha settings on production', function (): void {
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;

    $recaptcha = Formie::$plugin->getIntegrations()->getCaptchaByHandle('recaptcha');
    $recaptcha->scope = Integrations::SCOPE_PROJECT;
    $recaptcha->setEnabled(false);
    $recaptcha->siteKey = 'test-site-key';
    $recaptcha->secretKey = 'test-secret-key';
    $recaptcha->type = Recaptcha::RECAPTCHA_TYPE_ENTERPRISE;
    $recaptcha->projectId = 'test-project';

    expect(Formie::$plugin->getIntegrations()->saveCaptcha($recaptcha))->toBeTrue();

    expect(Formie::$plugin->getIntegrations()->savePostedCaptchaConfigs([
        'recaptcha' => [
            'type' => Recaptcha::class,
            'enabled' => '1',
            'settings' => [
                'type' => Recaptcha::RECAPTCHA_TYPE_ENTERPRISE,
                'projectId' => 'test-project',
            ],
        ],
    ]))->toBeTrue();

    $reloaded = Formie::$plugin->getIntegrations()->getCaptchaByHandle('recaptcha');

    expect($reloaded)->toBeInstanceOf(Recaptcha::class)
        ->and($reloaded->getEnabled())->toBeTrue()
        ->and($reloaded->getEnabledMenuValue())->toBe('1')
        ->and($reloaded->isSiteScope())->toBeTrue();
});

it('normalizes legacy enabled values for captcha boolean menus', function (): void {
    $recaptcha = Formie::$plugin->getIntegrations()->getCaptchaByHandle('recaptcha');
    $recaptcha->setEnabled('false');

    expect($recaptcha->getEnabledMenuValue())->toBe('0');

    $recaptcha->setEnabled('true');

    expect($recaptcha->getEnabledMenuValue())->toBe('1');
});
