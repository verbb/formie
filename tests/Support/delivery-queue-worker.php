<?php

declare(strict_types=1);

use Tests\Support\DeliveryWorkerFreshdesk;
use verbb\formie\services\Integrations;
use yii\base\Event;
use yii\mail\BaseMailer;

require dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/DeliveryWorkerFreshdesk.php';
Event::on(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, static function($event): void {
    $event->helpDesk[] = DeliveryWorkerFreshdesk::class;
});
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';
if (getenv('ENVIRONMENT') !== 'testing') {
    throw new RuntimeException('The delivery worker requires the owned test runtime.');
}
DeliveryWorkerFreshdesk::$directory = $argv[2];
DeliveryWorkerFreshdesk::$mode = $argv[3];
$app->getGc()->probability = 0;
$app->getQueue()->attempts = 1;
$mailer = $app->getMailer();
$mailer->useFileTransport = true;
$mailer->fileTransportPath = DeliveryWorkerFreshdesk::$directory . '/mail';
$mailer->on(BaseMailer::EVENT_BEFORE_SEND, static function($event): void {
    DeliveryWorkerFreshdesk::record('email');
    if (DeliveryWorkerFreshdesk::$mode === 'failure') {
        $event->isValid = false;
    }
});

// Run the real queue command against this fixture's job only. Other tests may have queued work.
$command = new \craft\queue\Command('queue', $app, [
    'queue' => $app->getQueue(), 'jobId' => $argv[1], 'isolate' => false,
]);
exit($command->runAction('run'));
