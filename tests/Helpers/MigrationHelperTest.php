<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\helpers\MigrationHelper;

it('skips plugin settings project config writes when allowAdminChanges is disabled', function (): void {
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;

    $plugin = Formie::$plugin;
    $settings = $plugin->getSettings()->toArray();
    $settings['pluginName'] = 'Formie Migration Test ' . uniqid();

    expect(MigrationHelper::savePluginSettingsIfAllowed($plugin, $settings))->toBeTrue()
        ->and($plugin->getSettings()->pluginName)->not->toBe($settings['pluginName']);
});

it('persists plugin settings to project config when allowAdminChanges is enabled', function (): void {
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = true;

    $plugin = Formie::$plugin;
    $originalName = $plugin->getSettings()->pluginName;
    $settings = $plugin->getSettings()->toArray();
    $settings['pluginName'] = 'Formie Migration Test ' . uniqid();

    expect(MigrationHelper::savePluginSettingsIfAllowed($plugin, $settings))->toBeTrue()
        ->and($plugin->getSettings()->pluginName)->toBe($settings['pluginName']);

    MigrationHelper::savePluginSettingsIfAllowed($plugin, array_merge($settings, [
        'pluginName' => $originalName,
    ]));
});
