<?php
require __DIR__ . '/upgrade-bootstrap.php';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';
require __DIR__ . '/upgrades/ExpandedFixture.php';
require __DIR__ . '/upgrades/RoundTwoFixture.php';

$action = $argv[1] ?? '';
$version = str_starts_with(\verbb\formie\Formie::$plugin->version, '3.') ? 3 : 4;
$expanded = new \FormieMigrationCoverage\ExpandedFixture(CRAFT_BASE_PATH, $version);
$relationships = new \FormieMigrationCoverage\RoundTwoFixture(CRAFT_BASE_PATH, $version);

if (!is_dir(CRAFT_BASE_PATH . '/.state')) mkdir(CRAFT_BASE_PATH . '/.state', 0775, true);

if ($action === 'seed') {
    $expanded->run('seed');
    $relationships->run('seed');
    if (!in_array('--single-site', $argv, true)) $relationships->run('sites');
} elseif ($action === 'permissions') {
    $relationships->run('permissions');
} elseif ($action === 'enrich') {
    $expanded->run('enrich');
} elseif ($action === 'capture') {
    $expanded->run('capture');
    $relationships->run('capture');
    $fixturePath = dirname(__DIR__, 2) . '/.cache/verbb-tests/upgrade-fixture.json';
    $fixture = json_decode(file_get_contents($fixturePath), true, 512, JSON_THROW_ON_ERROR);
    $fixture['integrationProjectConfig'] = $app->getProjectConfig()->get('formie.integrations', true);
    file_put_contents($fixturePath, json_encode($fixture, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
} elseif ($action === 'verify') {
    file_put_contents(CRAFT_BASE_PATH . '/.state/checks.json', json_encode(['checks' => [], 'pass' => true]));
    $expanded->run('verify');
} elseif ($action === 'behaviour') {
    $expanded->run('behaviour');
} elseif ($action === 'relationships') {
    $relationships->run('verify');
} elseif ($action === 'check') {
    $report = json_decode(file_get_contents(CRAFT_BASE_PATH . '/.state/checks.json'), true, 512, JSON_THROW_ON_ERROR);
    foreach ($report['checks'] as $check) {
        if (!$check['pass']) echo json_encode($check, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
    if (!$report['pass']) throw new RuntimeException('Populated historical corpus failed verification.');
    echo count($report['checks']) . " historical corpus assertions passed.\n";
} elseif (str_starts_with($action, 'probe:')) {
    $expanded->run($action);
} elseif ($action === 'snapshot') {
    file_put_contents(CRAFT_BASE_PATH . '/.state/snapshot-4.json', '{}');
    $expanded->run('snapshot');
    $relationships->run('snapshot');
} else {
    throw new RuntimeException('Unknown corpus action.');
}
