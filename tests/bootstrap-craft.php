<?php

declare(strict_types=1);

use craft\db\Query;
use Tests\Support\ResetTestDatabase;

require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/Support/ResetTestDatabase.php';

/** @var craft\console\Application $app */
try {
    $app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';
} catch (Throwable $e) {
    throw new RuntimeException(
        'Craft bootstrap failed for tests. Run `composer test:setup` and verify plugin `.env.testing` CRAFT_DB_* values.',
        0,
        $e
    );
}

if (!class_exists(Craft::class) || !Craft::$app) {
    throw new RuntimeException('Craft application failed to bootstrap for integration tests.');
}

$environment = getenv('ENVIRONMENT') ?: '';
if ($environment !== 'testing') {
    throw new RuntimeException('Refusing to run tests outside ENVIRONMENT=testing. Configure phpunit.craft.xml/.env.testing.');
}

$db = Craft::$app->getDb();
if (!$db->tableExists('{{%plugins}}')) {
    throw new RuntimeException(
        'Testing database is not installed yet. Run Craft install/migrations against .env.testing first, then re-run tests.'
    );
}

// In this isolated test app config path, plugin project-config keys can be absent
// even when plugin install rows exist; hydrate keys before the first plugin-service check.
$projectConfig = Craft::$app->getProjectConfig();
$pluginRows = (new Query())
    ->select(['handle', 'schemaVersion'])
    ->from('{{%plugins}}')
    ->where(['handle' => ['formie', 'freeform']])
    ->all();

foreach ($pluginRows as $pluginRow) {
    $handle = (string)($pluginRow['handle'] ?? '');
    if (!$handle) {
        continue;
    }

    $key = 'plugins.' . $handle;
    $pluginConfig = $projectConfig->get($key);

    if (!$pluginConfig || empty($pluginConfig['enabled'])) {
        $projectConfig->set($key, [
            ...($pluginConfig ?: []),
            'edition' => $handle === 'freeform' ? 'express' : 'standard',
            'enabled' => true,
            'schemaVersion' => (string)($pluginConfig['schemaVersion'] ?? $pluginRow['schemaVersion'] ?? ''),
        ]);
    }
}

// Craft can load the plugin service before this isolated test project config
// has plugin keys, which makes installed plugins appear absent and triggers a
// slow reinstall. Reload after hydrating the keys so tests only reset Formie
// data, rather than rebuilding the plugin schema at bootstrap.
$pluginsReflection = new ReflectionClass(Craft::$app->plugins);
foreach ([
    '_pluginsLoaded' => false,
    '_loadingPlugins' => false,
    '_plugins' => [],
] as $propertyName => $value) {
    if (!$pluginsReflection->hasProperty($propertyName)) {
        continue;
    }

    $property = $pluginsReflection->getProperty($propertyName);
    $property->setAccessible(true);
    $property->setValue(Craft::$app->plugins, $value);
}

$plugins = Craft::$app->plugins;
if (!$plugins->isPluginEnabled('formie')) {
    $plugins->installPlugin('formie');
}

ResetTestDatabase::resetFormieData();
