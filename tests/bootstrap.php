<?php
/**
 * Plugin-local base bootstrap for tests and test setup.
 */

declare(strict_types=1);

$pluginRoot = dirname(__DIR__);

// Define path constants for plugin-local Craft bootstrap.
defined('CRAFT_BASE_PATH') || define('CRAFT_BASE_PATH', $pluginRoot);
defined('CRAFT_VENDOR_PATH') || define('CRAFT_VENDOR_PATH', CRAFT_BASE_PATH . '/vendor');
defined('CRAFT_CONFIG_PATH') || define('CRAFT_CONFIG_PATH', CRAFT_BASE_PATH . '/tests/_craft/config');
defined('CRAFT_STORAGE_PATH') || define('CRAFT_STORAGE_PATH', CRAFT_BASE_PATH . '/tests/_craft/storage');
defined('CRAFT_RUNTIME_PATH') || define('CRAFT_RUNTIME_PATH', CRAFT_STORAGE_PATH . '/runtime');
defined('CRAFT_WEB_ROOT') || define('CRAFT_WEB_ROOT', CRAFT_BASE_PATH . '/tests/_craft/web');

// Ensure local storage/web directories exist for standalone test contributors.
foreach ([CRAFT_STORAGE_PATH, CRAFT_RUNTIME_PATH, CRAFT_WEB_ROOT] as $path) {
    if (!is_dir($path)) {
        mkdir($path, 0775, true);
    }
}

// Load Composer autoloader from plugin-local vendor dir.
require_once CRAFT_VENDOR_PATH . '/autoload.php';

// Use a dedicated dotenv file for tests, defaulting to `.env.testing`.
$dotenvFile = getenv('DOTENV_FILE') ?: '.env.testing';
$dotenvPath = CRAFT_BASE_PATH . DIRECTORY_SEPARATOR . $dotenvFile;
putenv("DOTENV_FILE={$dotenvFile}");
putenv("CRAFT_DOTENV_PATH={$dotenvPath}");

// Load dotenv with support for selecting a dedicated env file for tests.
if (class_exists(Dotenv\Dotenv::class) && file_exists($dotenvPath)) {
    Dotenv\Dotenv::createUnsafeMutable(CRAFT_BASE_PATH, [$dotenvFile])->safeLoad();
}

