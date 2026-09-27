<?php

require dirname(__DIR__) . '/bootstrap.php';
require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';
if (getenv('ENVIRONMENT') !== 'testing') {
    throw new RuntimeException('Outbox worker requires the testing environment.');
}
$mutex = Craft::$app->getMutex();
$lock = 'formie.tests.dispatch-worker-bootstrap';
if (!$mutex->acquire($lock, 10)) {
    throw new RuntimeException('Unable to initialize the outbox fixture.');
}
try {
    new \verbb\formie\elements\Submission();
} finally {
    $mutex->release($lock);
}
\yii\base\Event::on(\verbb\formie\services\SubmissionWorkflow::class, \verbb\formie\services\SubmissionWorkflow::EVENT_BEFORE_STAGE,
    function ($event) use ($argv) {
        if ((int)$event->command->submission->id === (int)$argv[1]) {
            file_put_contents($argv[3], $event->stage . "\n", FILE_APPEND | LOCK_EX);
            usleep(200000);
        }
    });
\verbb\formie\Formie::$plugin->getSubmissionDispatches()->resume((int)$argv[1], $argv[2]);
