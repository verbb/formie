<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\models\Settings;
use verbb\formie\services\Integrations;
use verbb\formie\services\SpamProtection;

it('stores spam settings in the runtime database table', function (): void {
    $row = (new \craft\db\Query())
        ->from([Table::FORMIE_SPAM_SETTINGS])
        ->one();

    expect($row)->not->toBeNull()
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

it('promotes project-scoped spam settings to site scope on production save', function (): void {
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = true;
    Formie::$plugin->getSpamProtection()->saveValues(array_merge(
        Formie::$plugin->getSpamProtection()->getSettingsValues(),
        ['scope' => Integrations::SCOPE_PROJECT],
    ));
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;

    $settings = Formie::$plugin->getSettings();
    $settings->spamKeywords = 'promoted-keyword';

    expect(Formie::$plugin->getSpamProtection()->saveFromSettings($settings))->toBeTrue()
        ->and(Formie::$plugin->getSpamProtection()->isSiteScope())->toBeTrue();
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
