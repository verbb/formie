<?php

require dirname(__DIR__, 2) . '/.github/scripts/runtime-preflight.php';

// Discovery loads the whole suite even with --filter. Match the full runner's
// budget before Pest starts, preserving higher or unlimited interpreter limits.
$memoryLimit = ini_get('memory_limit');
if ($memoryLimit !== false && $memoryLimit !== '-1' && ini_parse_quantity($memoryLimit) < 1024 * 1024 * 1024) {
    if (ini_set('memory_limit', '1G') === false) {
        throw new RuntimeException('Pest requires at least 1 GB of memory for test discovery.');
    }
}

// Pest derives its root from vendor/, which belongs to the generated Craft app.
// Point its discovery at the checkout so Pest.php, helpers and datasets load too.
require_once __DIR__ . '/bootstrap.php';
require dirname(__DIR__, 2) . '/.github/scripts/pest-exit.php';
$_SERVER['argv'][] = '--test-directory=../../../tests';
require CRAFT_VENDOR_PATH . '/pestphp/pest/bin/pest';
