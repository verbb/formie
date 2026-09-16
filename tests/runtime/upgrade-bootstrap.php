<?php
// A separate app and table prefix keep this fixture independent of the current schema.
$root = dirname(__DIR__, 2);
$owner = json_decode((string)@file_get_contents($root . '/.cache/verbb-tests/owner.json'), true);
if (getenv('IS_DDEV_PROJECT') !== 'true' || ($owner['project'] ?? null) !== getenv('DDEV_SITENAME') || ($owner['source'] ?? null) !== realpath($root)) {
    throw new RuntimeException('Upgrade fixtures require the owned disposable DDEV app.');
}
$appRoot = $root . '/.cache/verbb-tests/upgrade-app';
foreach (['.cache', '.cache/verbb-tests', '.cache/verbb-tests/upgrade-app'] as $path) {
    if (is_link($root . '/' . $path)) { throw new RuntimeException('Refusing symlinked upgrade runtime.'); }
}
foreach (getenv() as $key => $value) {
    if (str_starts_with($key, 'CRAFT_') || in_array($key, ['DOTENV_FILE', 'ENVIRONMENT', 'PRIMARY_SITE_URL'], true)) { putenv($key); unset($_ENV[$key], $_SERVER[$key]); }
}
foreach (['CRAFT_ENVIRONMENT' => 'testing', 'ENVIRONMENT' => 'testing', 'CRAFT_SECURITY_KEY' => 'disposable-upgrade-fixture-only',
    'CRAFT_APP_ID' => 'FormieUpgradeContract', 'PRIMARY_SITE_URL' => 'https://formie-react-tests.ddev.site'] as $key => $value) {
    putenv($key . '=' . $value); $_ENV[$key] = $_SERVER[$key] = $value;
}
define('CRAFT_BASE_PATH', $appRoot);
define('CRAFT_VENDOR_PATH', $appRoot . '/vendor');
define('CRAFT_CONFIG_PATH', $appRoot . '/config');
define('CRAFT_STORAGE_PATH', $appRoot . '/storage');
define('CRAFT_RUNTIME_PATH', CRAFT_STORAGE_PATH . '/runtime');
define('CRAFT_WEB_ROOT', $appRoot . '/web');
require CRAFT_VENDOR_PATH . '/autoload.php';
