<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\helpers\SpamProtectionSettings as Management;
use verbb\formie\helpers\Table;
use verbb\formie\models\Settings;
use verbb\formie\services\Integrations;
use verbb\formie\services\SpamProtection;

beforeEach(function(): void {
    $this->spamTransaction = Craft::$app->getDb()->beginTransaction();
    Management::resetCaches();
});
afterEach(function(): void {
    $this->spamTransaction->rollBack();
    Craft::$app->getInfo()->configVersion = (new \craft\db\Query())->select('configVersion')->from(\craft\db\Table::INFO)->scalar();
    Craft::$app->getProjectConfig()->reset();
    Management::resetCaches();
});

it('stores spam settings in the runtime database table', function (): void {
    $spamProtection = Formie::$plugin->getSpamProtection();
    expect($spamProtection->saveValues($spamProtection->getSettingsValues()))->toBeTrue();

    $row = (new \craft\db\Query())
        ->from([Table::FORMIE_SPAM_SETTINGS])
        ->one();

    expect($row)->toBeArray()
        ->and($row['scope'])->toBeIn([Integrations::SCOPE_PROJECT, Integrations::SCOPE_SITE]);
});

it('hydrates plugin settings from the spam protection store', function (): void {
    $spamProtection = Formie::$plugin->getSpamProtection();
    $values = $spamProtection->getSettingsValues();

    $spamProtection->saveValues(array_merge($values, [
        'spamKeywords' => 'test-keyword',
        'spamLimit' => 42,
    ]));

    $settings = new Settings();
    $spamProtection->hydrateSettings($settings);

    expect($settings->spamKeywords)->toBe('test-keyword')
        ->and($settings->spamLimit)->toBe(42);
});

it('saves site-scoped spam settings when admin changes are disabled', function (): void {
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;

    $spamProtection = Formie::$plugin->getSpamProtection();
    $settings = Formie::$plugin->getSettings();

    $settings->spamKeywords = 'site-scoped-keyword';
    $settings->spamLimit = 99;

    expect($spamProtection->saveValues(array_merge($spamProtection->getSettingsValues(), [
        'scope' => Integrations::SCOPE_SITE,
        'spamKeywords' => 'site-scoped-keyword',
        'spamLimit' => 99,
    ])))->toBeTrue();

    Formie::$plugin->getSpamProtection()->hydrateSettings($settings);

    expect(Formie::$plugin->getSpamProtection()->isSiteScope())->toBeTrue()
        ->and($settings->spamKeywords)->toBe('site-scoped-keyword')
        ->and($settings->spamLimit)->toBe(99);
});

it('allows production editing of formerly project-scoped spam rules', function (): void {
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SPAM_SETTINGS, ['scope' => 'project'])->execute();
    Management::resetCaches();
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;
    $settings = Formie::$plugin->getSettings();
    $settings->spamKeywords = 'production-rule';
    expect(Formie::$plugin->getSpamProtection()->saveFromSettings($settings))->toBeTrue()
        ->and(Formie::$plugin->getSpamProtection()->isSiteScope())->toBeTrue()
        ->and(Formie::$plugin->getSpamProtection()->getSettingsValues()['spamKeywords'])->toBe('production-rule');
});

it('strips spam keys from plugin settings payloads', function (): void {
    $stripped = Formie::$plugin->getSpamProtection()->stripFromPluginSettingsArray([
        'pluginName' => 'Formie',
        'saveSpam' => false,
        'spamKeywords' => 'secret',
    ]);

    expect($stripped)->toBe(['pluginName' => 'Formie'])
        ->and($stripped)->not->toHaveKey('saveSpam')
        ->and($stripped)->not->toHaveKey('spamKeywords');
});

it('never exports spam rules even when callers request project scope', function (): void {
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = true;
    $before = Craft::$app->getProjectConfig()->get('formie');
    $service = Formie::$plugin->getSpamProtection();
    expect($service->saveValues([...$service->getSettingsValues(), 'scope' => 'project', 'spamKeywords' => 'local-only']))->toBeTrue();
    expect($service->isSiteScope())->toBeTrue()
        ->and(Craft::$app->getProjectConfig()->get('formie'))->toEqual($before)
        ->and(\verbb\formie\helpers\ProjectConfigHelper::rebuildProjectConfig())->not->toHaveKey('spamSettings');
});
