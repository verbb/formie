<?php
// Every mutant is a separate source copy loaded in a child process. The checkout
// is never edited, and a failing baseline or PHP error cannot count as a kill.
require_once __DIR__ . '/bootstrap.php';
$root = dirname(__DIR__, 2);
$out = $root . '/.cache/verbb-tests/mutations';
@mkdir($out, 0775, true);
$cases = [
    'encrypted-content-lost' => ['base/Field.php', '$value = StringHelper::encenc($value);', '$value = null;', 'tests/Fields/FieldBehaviorEncryptionTest.php', 'stores encrypted values'],
    'required-validation-bypassed' => ['helpers/ValidationHelper.php', '$scenario === Element::SCENARIO_LIVE && $field->required', 'false && $field->required', 'tests/Fields/FieldBehaviorRequiredTest.php', 'rejects an empty required email'],
    'conversion-erases-values' => ['base/Integration.php', 'return IntegrationHelper::convertValueForIntegration($value, $integrationField);', 'return null;', 'tests/Integrations/IntegrationFieldMappingMatrixTest.php', 'preserves mapped values'],
    'permission-check-removed' => ['controllers/SubmissionsController.php', 'if (!$currentUser || !$submission->canView($currentUser)) {', 'if (false) {', 'tests/Security/CP/SubmissionSideEffectAclSecurityTest.php', 'enforces persisted user permissions'],
    'notification-delivered-twice' => ['helpers/DeliveryAttempt.php', "if (\$meta['state'] === 'completed') {", 'if (false) {', 'tests/Notifications/NotificationDeliveryRecoveryTest.php', 'checkpoints individual notifications'],
    'migration-drops-scalar' => ['migrations/plugins/MigrateFreeform5.php', '$submission->setFieldValue($handle, $field->getValue());', '$submission->setFieldValue($handle, null);', 'tests/Migrations/MigrateFreeform5ContractsTest.php', 'migrates a large freeform'],
];
$results = [];
foreach ($cases as $name => [$relative, $original, $replacement, $test, $filter]) {
    $source = file_get_contents($root . '/src/' . $relative);
    if (substr_count($source, $original) !== 1) {
        throw new RuntimeException("Mutation no longer matches exactly once: $name");
    }
    $class = 'verbb\\formie\\' . str_replace('/', '\\', substr($relative, 0, -4));
    foreach (['baseline', 'mutant'] as $mode) {
        $prefix = "$out/$name-$mode";
        @unlink($prefix . '.xml');
        file_put_contents($prefix . '.php', $mode === 'mutant' ? str_replace($original, $replacement, $source) : $source);
        file_put_contents($prefix . '.json', json_encode(['class' => $class, 'file' => $prefix . '.php']));
        $command = ['php', '-d', 'memory_limit=1G', 'tests/runtime/mutation-pest.php', $prefix . '.json', '-c', 'phpunit.craft.xml', $test, '--filter=' . $filter, '--log-junit=' . $prefix . '.xml', '--fail-on-empty-test-suite'];
        $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['file', $prefix . '.log', 'w'], 2 => ['file', $prefix . '.err', 'w']], $pipes, $root);
        $exit = proc_close($process);
        $report = is_file($prefix . '.xml') ? simplexml_load_file($prefix . '.xml') : false;
        $suite = $report ? $report->testsuite : null;
        $valid = $suite && (int)$suite['tests'] > 0 && (int)$suite['errors'] === 0 && (int)$suite['skipped'] === 0;
        $passed = $valid && ($mode === 'baseline' ? $exit === 0 && (int)$suite['failures'] === 0 : $exit !== 0 && (int)$suite['failures'] > 0);
        $results[$name][$mode] = ['valid' => (bool)$valid, 'expectedOutcome' => $passed, 'exit' => $exit];
        file_put_contents($out . '/results.json', json_encode($results, JSON_PRETTY_PRINT));
        if (!$valid || !$passed) {
            throw new RuntimeException("Unexpected $mode result for $name; inspect $prefix.log");
        }
    }
    echo "Detected mutation: $name\n";
}
