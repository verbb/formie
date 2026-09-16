<?php
require_once __DIR__ . '/bootstrap.php';
$specPath = $_SERVER['argv'][1];
$allowedRoot = realpath(dirname(__DIR__, 2) . '/.cache/verbb-tests/mutations');
if (!$allowedRoot || !str_starts_with((string)realpath($specPath), $allowedRoot . '/')) {
    throw new RuntimeException('Mutation specifications must belong to this disposable runtime.');
}
$spec = json_decode(file_get_contents($specPath), true, flags: JSON_THROW_ON_ERROR);
array_splice($_SERVER['argv'], 1, 1);
spl_autoload_register(static function (string $class) use ($spec): void {
    if ($class === $spec['class']) {
        require $spec['file'];
    }
}, true, true);
require __DIR__ . '/pest.php';
