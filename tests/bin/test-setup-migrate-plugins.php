<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/** @var craft\console\Application $app */
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';

if (!class_exists(Craft::class) || !Craft::$app) {
    fwrite(STDERR, "Craft bootstrap failed for migrate-plugin test setup.\n");
    exit(1);
}

$environment = getenv('ENVIRONMENT') ?: '';
if ($environment !== 'testing') {
    fwrite(STDERR, "Refusing to run migrate-plugin setup outside ENVIRONMENT=testing.\n");
    exit(1);
}

$plugins = Craft::$app->plugins;

if (!$plugins->isPluginEnabled('formie')) {
    $plugins->installPlugin('formie');
}

if (!$plugins->isPluginEnabled('freeform')) {
    $plugins->installPlugin('freeform');
}

fwrite(STDOUT, "Migrate-plugin setup complete: formie + freeform enabled.\n");
