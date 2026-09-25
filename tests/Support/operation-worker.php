<?php

require dirname(__DIR__) . '/bootstrap.php';
require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';
require __DIR__ . '/submission-workflow.php';

if (getenv('ENVIRONMENT') !== 'testing') {
    throw new RuntimeException('Operation worker requires the testing environment.');
}
$mutex = Craft::$app->getMutex();
$key = 'formie.tests.operation-worker-bootstrap';
if (!$mutex->acquire($key, 10)) {
    throw new RuntimeException('Unable to initialise the operation fixture.');
}
try {
    $submission = \verbb\formie\elements\Submission::find()->id((int)$argv[1])->status(null)->isIncomplete(null)->one();
    $form = $submission->getForm();
    $command = submissionCommand([
        'form' => $form, 'submission' => $submission,
        'operation' => \verbb\formie\enums\SubmissionOperation::SAVE_DRAFT,
        'navigation' => \verbb\formie\enums\NavigationIntent::STAY,
        'expectedVersion' => (int)$argv[2], 'operationId' => 'parallel-operation', 'payload' => ['answer' => 'once'],
    ]);
} finally {
    $mutex->release($key);
}
$outcome = \verbb\formie\Formie::$plugin->getSubmissionOperations()->execute($command, function () use ($command, $argv) {
    file_put_contents($argv[3], "mutated\n", FILE_APPEND | LOCK_EX);
    usleep(200000);
    return \verbb\formie\Formie::$plugin->getSubmissionWorkflow()->process($command);
});
echo $outcome->type->value . ':' . $outcome->version;
