<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\helpers\{DbSchema, Table};
use verbb\formie\models\Settings;
use verbb\formie\services\SpamProtection;

it('converts legacy field defaults before the plugin instance has initialized', function (): void {
    $plugin = Formie::$plugin;
    Formie::$plugin = null;
    try {
        $settings = new Settings();
        $settings->setAttributes(['defaultFileUploadVolume' => 'folder:fixture', 'defaultDateDisplayType' => 'dropdowns', 'defaultDateValueOption' => 'today'], false);
        expect($settings->fieldDefaults[\verbb\formie\fields\FileUpload::class]['uploadLocationSource'])->toBe('folder:fixture');
        expect($settings->fieldDefaults[\verbb\formie\fields\Date::class])->toMatchArray(['displayType' => 'dropdowns', 'defaultOption' => 'today']);
        $settings->setAttributes(['pluginName' => 'Renamed']);
        expect($settings->fieldDefaults[\verbb\formie\fields\Date::class]['defaultOption'])->toBe('today');
    } finally { Formie::$plugin = $plugin; }
});

it('preserves legacy spam values until the new store has been seeded', function (): void {
    $transaction = Craft::$app->getDb()->beginTransaction();
    try {
    Craft::$app->getDb()->createCommand()->delete(Table::FORMIE_SPAM_SETTINGS)->execute();
    $settings = new Settings(['saveSpam' => false, 'spamLimit' => 137, 'spamKeywords' => 'legacy-keyword']);
    $service = new SpamProtection();
    $service->hydrateSettings($settings);
    expect($settings->saveSpam)->toBeFalse()->and($settings->spamLimit)->toBe(137)->and($settings->spamKeywords)->toBe('legacy-keyword');
    $service->seedFromLegacySettings($settings->toArray());
    expect($service->getSettingsValues())->toMatchArray(['saveSpam' => false, 'spamLimit' => 137, 'spamKeywords' => 'legacy-keyword']);
    } finally { $transaction->rollBack(); }
});

it('sees tables and columns created after an earlier missing-schema lookup', function (): void {
    $db = Craft::$app->getDb();
    $table = '{{%formie_schema_probe_' . bin2hex(random_bytes(4)) . '}}';
    expect(DbSchema::tableExists($table))->toBeFalse();
    expect(DbSchema::columnExists($table, 'value'))->toBeFalse();
    try {
        $db->createCommand()->createTable($table, ['id' => 'pk'])->execute();
        expect(DbSchema::tableExists($table))->toBeTrue();
        expect(DbSchema::columnExists($table, 'value'))->toBeFalse();
        $db->createCommand()->addColumn($table, 'value', 'string')->execute();
        expect(DbSchema::columnExists($table, 'value'))->toBeTrue();
    } finally { $db->createCommand()->dropTable($table)->execute(); }
});
