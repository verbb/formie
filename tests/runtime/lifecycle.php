<?php
// Run as ddev test --task=lifecycle; the runner owns the disposable runtime lock.
require __DIR__ . '/bootstrap.php';
$mode = $argv[1] ?? 'run';
$stateFile = dirname(__DIR__, 2) . '/.cache/verbb-tests/lifecycle-state.json';

if ($mode === 'run') {
    $run = static function (array $command): void {
        $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes);
        if (!is_resource($process) || proc_close($process) !== 0) {
            throw new RuntimeException('Lifecycle contract failed: ' . implode(' ', $command));
        }
    };
    $run(['php', __FILE__, 'seed']);
    $run(['php', __DIR__ . '/craft.php', 'plugin/disable', 'formie', '--interactive=0']);
    $run(['php', __FILE__, 'disabled']);
    $run(['php', __DIR__ . '/craft.php', 'plugin/enable', 'formie', '--interactive=0']);
    $run(['php', __FILE__, 'enabled']);
    $run(['php', __DIR__ . '/craft.php', 'plugin/uninstall', 'formie', '--interactive=0']);
    $run(['php', __FILE__, 'uninstalled']);
    $run(['php', __DIR__ . '/craft.php', 'plugin/install', 'formie', '--interactive=0']);
    $run(['php', __FILE__, 'reinstalled']);
    echo "Populated disable, enable, uninstall and reinstall contracts passed.\n";
    return;
}

$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';
$check = static function (bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
    echo 'Verified: ' . $message . PHP_EOL;
};
$types = [\verbb\formie\elements\Form::class, \verbb\formie\elements\Submission::class, \verbb\formie\elements\SentNotification::class];
if (in_array($mode, ['seed', 'reinstalled'], true)) {
    $check($app->getPlugins()->isPluginEnabled('formie'), 'Formie is enabled');
    $statusHandles = array_column(\verbb\formie\Formie::$plugin->getFormStatuses()->getAllStatuses(), 'handle');
    sort($statusHandles);
    $check($statusHandles === ['active', 'archived', 'draft'], 'Installation seeds the standard form statuses');
    $check(\verbb\formie\Formie::$plugin->getStencils()->getStencilByHandle('contactForm') !== null, 'Installation seeds the Contact Form stencil');
    $form = \verbb\formie\Formie::$plugin->getFactories()->form(['handle' => 'lifecycleContact'])
        ->singleLineTextField('message')->create();
    $submission = \verbb\formie\Formie::$plugin->getFactories()->submission($form)->with(['message' => 'Lifecycle content'])->save();
    $reloaded = \verbb\formie\elements\Submission::find()->id($submission->id)->one();
    $check($reloaded?->getFieldValue('message') === 'Lifecycle content', 'Installed plugin saves and reloads content');
}
$tables = array_values(array_filter($app->getDb()->getSchema()->getTableNames(), fn($name) => str_starts_with($name, $app->getDb()->tablePrefix . 'formie_')));
$elements = (new \craft\db\Query())->from('{{%elements}}')->where(['type' => $types])->orderBy('id')->column();
$state = ['tables' => $tables, 'elements' => $elements];
if ($mode === 'seed') {
    file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT));
} elseif (in_array($mode, ['disabled', 'enabled'], true)) {
    $check($app->getPlugins()->isPluginEnabled('formie') === ($mode === 'enabled'), 'Plugin enablement matches ' . $mode);
    $check($state === json_decode(file_get_contents($stateFile), true), 'Disabling and enabling preserve tables and element identities');
} elseif ($mode === 'uninstalled') {
    $check(!$app->getPlugins()->isPluginInstalled('formie'), 'Plugin is uninstalled');
    $check($elements === [], 'Uninstall removes Formie elements');
    $check($tables === [], 'Uninstall removes all Formie tables: ' . implode(', ', $tables));
} elseif ($mode === 'reinstalled') {
    $check($tables === json_decode(file_get_contents($stateFile), true)['tables'], 'Reinstall recreates the original schema without archived leftovers');
}
