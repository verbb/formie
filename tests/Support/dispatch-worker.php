<?php

// Independent process for DispatchRecoveryTest; never resets the fixture DB.
require dirname(__DIR__) . '/bootstrap.php';
require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';

if (getenv('ENVIRONMENT') !== 'testing') {
    throw new RuntimeException('Delivery worker requires the testing environment.');
}

// Craft generates and prunes compiled field behaviours on the first element
// construction. Serialize fixture startup, but leave delivery concurrent.
$mutex = Craft::$app->getMutex();
$bootstrapLock = 'formie.tests.dispatch-worker-bootstrap';
if (!$mutex->acquire($bootstrapLock, 10)) {
    throw new RuntimeException('Unable to initialize the delivery worker fixture.');
}
try {
    $submission = new \verbb\formie\elements\Submission(['id' => (int)$argv[1]]);
} finally {
    $mutex->release($bootstrapLock);
}
$state = new \verbb\formie\workflow\tasks\dispatch\DispatchState(new \verbb\formie\models\SubmissionRequest([
    'submission' => $submission,
    'requestToken' => 'concurrent-delivery',
]), true);

$state->runOnce('concurrent', function () use ($argv): void {
    file_put_contents($argv[2], "delivered\n", FILE_APPEND | LOCK_EX);
    usleep(200000);
});
