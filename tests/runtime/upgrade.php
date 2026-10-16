<?php
// Run as ddev test --task=upgrade; the canonical runner owns the exclusive runtime lock.
require __DIR__ . '/bootstrap.php';
$root = dirname(__DIR__, 2);
$dir = $root . '/.cache/verbb-tests/upgrade-app';
foreach ([$dir, $dir . '/config', $dir . '/storage', $dir . '/web', $dir . '/.state'] as $path) {
    if (is_link($path)) { throw new RuntimeException('Refusing symlinked upgrade fixture.'); }
    if (!is_dir($path)) { mkdir($path, 0775, true); }
}
foreach (['checks.json', 'coverage-behaviour.json', 'form-edit.json', 'probe-notification.json', 'probe-form-settings.json', 'probe-submission.json', 'snapshot-4.json'] as $file) {
    if (is_file($dir . '/.state/' . $file)) { unlink($dir . '/.state/' . $file); }
}
$run = function (array $command, ?string $cwd = null): void {
    $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, $cwd);
    if (!is_resource($process) || proc_close($process) !== 0) { throw new RuntimeException('Upgrade contract command failed: ' . implode(' ', $command)); }
};
$manifest = ['name' => 'verbb/formie-upgrade-contract', 'require' => ['craftcms/cms' => '5.11.1', 'verbb/formie' => '3.1.39'],
    'config' => ['allow-plugins' => ['craftcms/plugin-installer' => true, 'yiisoft/yii2-composer' => true]], 'minimum-stability' => 'stable'];
file_put_contents($dir . '/composer.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
// The old and new versions are installed in separate processes; no class alias simulates an upgrade.
copy(__DIR__ . '/upgrade-composer.lock', $dir . '/composer.lock');
$run(['composer', 'install', '--no-interaction', '--prefer-dist', '--no-progress'], $dir);
file_put_contents($dir . '/config/db.php', "<?php\nreturn ['driver'=>'mysql','server'=>'db','port'=>'3306','database'=>'db','user'=>'db','password'=>'db','tablePrefix'=>'upg_'];\n");
file_put_contents($dir . '/config/general.php', "<?php\nreturn \\craft\\config\\GeneralConfig::create()->devMode(true)->allowAdminChanges(true)->timezone('UTC');\n");
file_put_contents($dir . '/config/app.php', "<?php\nreturn ['baseApiUrl'=>'http://127.0.0.1:9/','components'=>['projectConfig'=>['class'=>'craft\\services\\ProjectConfig','writeYamlAutomatically'=>false]]];\n");
$db = new PDO('mysql:host=db;dbname=db', 'db', 'db', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('SET FOREIGN_KEY_CHECKS=0');
foreach ($db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
    if (str_starts_with($table, 'upg_')) { $db->exec('DROP TABLE `' . str_replace('`', '``', $table) . '`'); }
}
$db->exec('SET FOREIGN_KEY_CHECKS=1');
$run(['php', 'tests/runtime/upgrade-craft.php', 'install/craft', '--interactive=0', '--username=admin', '--email=admin@example.test', '--password=testing-only-password', '--siteName=Upgrade fixture', '--siteUrl=https://formie-react-tests.ddev.site', '--language=en-US']);
$run(['php', 'tests/runtime/upgrade-craft.php', 'plugin/install', 'formie', '--interactive=0']);
$run(['php', 'tests/runtime/upgrade-seed.php', ...(in_array('--single-site', $argv, true) ? ['--single-site'] : [])]);
$siteArgs = in_array('--single-site', $argv, true) ? ['--single-site'] : [];
// These files belong to this disposable fixture, and must never survive its database reset.
foreach (['coverage-map.json', 'round-two-map.json'] as $file) {
    if (is_file($dir . '/' . $file)) { unlink($dir . '/' . $file); }
}
$run(['php', 'tests/runtime/upgrade-corpus.php', 'seed', ...$siteArgs]);
$run(['php', 'tests/runtime/upgrade-corpus.php', 'enrich']);
$run(['php', 'tests/runtime/upgrade-corpus.php', 'permissions']);
$run(['php', 'tests/runtime/upgrade-corpus.php', 'capture']);
$sourceFiles = [$dir . '/coverage-map.json', $dir . '/round-two-map.json', $root . '/.cache/verbb-tests/upgrade-fixture.json'];
$sourceHashes = array_map(fn($file) => hash_file('sha256', $file), $sourceFiles);
// Remove only the old Composer package before installing a path dependency inside its source tree.
// The installed plugin and populated database remain in place for Craft's upgrade migrations.
$run(['composer', 'remove', 'verbb/formie', '--no-interaction', '--no-progress'], $dir);
$manifest['require']['verbb/formie'] = '*';
$manifest['repositories'] = [['type' => 'path', 'url' => '../../..', 'options' => ['symlink' => true]]];
$manifest['minimum-stability'] = 'dev';
$manifest['prefer-stable'] = true;
file_put_contents($dir . '/composer.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
$run(['composer', 'update', '--no-interaction', '--prefer-dist', '--no-progress', '--with-all-dependencies'], $dir);
$run(['php', 'tests/runtime/upgrade-craft.php', 'migrate/all', '--interactive=0']);
$run(['php', 'tests/runtime/upgrade-verify.php']);
$run(['php', 'tests/runtime/upgrade-corpus.php', 'verify']);
// Fresh processes prevent rolled-back behaviour checks or service caches from tainting probes.
$run(['php', 'tests/runtime/upgrade-corpus.php', 'behaviour']);
$run(['php', 'tests/runtime/upgrade-corpus.php', 'relationships']);
$run(['php', 'tests/runtime/upgrade-corpus.php', 'check']);
foreach (['notification', 'form-settings', 'submission'] as $area) {
    $run(['php', 'tests/runtime/upgrade-corpus.php', 'probe:' . $area]);
}
$run(['php', 'tests/runtime/upgrade-corpus.php', 'snapshot']);
$firstSnapshot = file_get_contents($dir . '/.state/snapshot-4.json');
$run(['php', 'tests/runtime/upgrade-craft.php', 'migrate/all', '--interactive=0']);
$run(['php', 'tests/runtime/upgrade-corpus.php', 'snapshot']);
if ($firstSnapshot !== file_get_contents($dir . '/.state/snapshot-4.json')) {
    throw new RuntimeException('A second migration run changed the upgraded corpus.');
}
if ($sourceHashes !== array_map(fn($file) => hash_file('sha256', $file), $sourceFiles)) {
    throw new RuntimeException('Verification modified the historical expectations.');
}
echo "Historical source expectations unchanged; repeated migration is stable.\n";
