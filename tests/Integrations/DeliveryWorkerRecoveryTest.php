<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\FileHelper;
use Tests\Support\DeliveryWorkerFreshdesk;
use verbb\formie\Formie;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\jobs\DeliveryJobInterface;
use verbb\formie\models\Notification;
use verbb\formie\services\DeliveryAttempts;
use verbb\formie\services\Integrations;
use yii\base\Event;

function deliveryWorkerReceipts(string $directory): array
{
    $path = $directory . '/receipts.jsonl';
    return is_file($path) ? array_map(static fn($line) => json_decode($line, true, flags: JSON_THROW_ON_ERROR)['step'], file($path, FILE_IGNORE_NEW_LINES)) : [];
}

function runDeliveryQueueWorker(string $jobId, string $directory, string $mode, int $attempts = 1): array
{
    $output = $directory . '/worker-' . $mode . '.log';
    @unlink($directory . '/ready');
    $process = proc_open([PHP_BINARY, dirname(__DIR__) . '/Support/delivery-queue-worker.php', $jobId, $directory, $mode, (string)$attempts], [
        0 => ['file', '/dev/null', 'r'], 1 => ['file', $output, 'w'], 2 => ['redirect', 1],
    ], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start the delivery queue worker.');
    }
    $terminated = false;
    try {
        $deadline = microtime(true) + 40;
        do {
            clearstatcache(true, $directory . '/ready');
            if (!$terminated && $mode === 'interrupt' && is_file($directory . '/ready')) {
                expect(proc_terminate($process, 9))->toBeTrue();
                $terminated = true;
            }
            $status = proc_get_status($process);
            if (!$status['running']) {
                return $status + ['output' => file_get_contents($output), 'terminated' => $terminated];
            }
            usleep(20000);
        } while (microtime(true) < $deadline);
        throw new RuntimeException('Delivery worker timed out: ' . file_get_contents($output));
    } finally {
        if (proc_get_status($process)['running']) {
            proc_terminate($process, 9);
        }
        proc_close($process);
    }
}

function withDeliveryQueueFixture(string $kind, callable $callback): void
{
    $directory = Craft::$app->getPath()->getTempPath() . '/delivery-worker-' . bin2hex(random_bytes(8));
    FileHelper::createDirectory($directory);
    DeliveryWorkerFreshdesk::$directory = $directory;
    DeliveryWorkerFreshdesk::$mode = 'success';
    $register = static function($event): void { $event->helpDesk[] = DeliveryWorkerFreshdesk::class; };
    Event::on(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, $register);
    $queue = Craft::$app->getQueue();
    $originalTtr = $queue->ttr;
    $originalAttempts = $queue->attempts;
    $queue->ttr = 2;
    $queue->attempts = 1;
    $jobId = null;
    $integration = null;
    $form = null;
    $capture = static function($event) use (&$jobId): void {
        if ($event->job instanceof DeliveryJobInterface) {
            $jobId = (string)$event->id;
        }
    };
    $queue->on(\yii\queue\Queue::EVENT_AFTER_PUSH, $capture);
    try {
        $form = formie()->form()->singleLineTextField('name')->emailField('email')->create();
        $execution = 'worker-' . bin2hex(random_bytes(8));
        if ($kind === 'integration') {
            $integration = new DeliveryWorkerFreshdesk([
                'name' => 'Worker recovery', 'handle' => 'worker' . bin2hex(random_bytes(8)),
                'scope' => Integrations::SCOPE_SITE, 'enabled' => true,
                'apiDomain' => 'https://8.8.8.8', 'apiKey' => 'synthetic-local-handler',
            ]);
            expect(Formie::$plugin->getIntegrations()->saveIntegration($integration))->toBeTrue();
            $form->settings->integrations = [$integration->handle => [
                'enabled' => true, 'mapToContact' => true, 'mapToTicket' => true,
                'contactFieldMapping' => ['name' => '{field:name}', 'email' => '{field:email}'],
                'ticketFieldMapping' => [
                    'name' => '{field:name}', 'email' => '{field:email}', 'subject' => 'Worker recovery',
                    'description' => 'Local fixture', 'status' => '2', 'priority' => '1', 'source' => '2',
                ],
            ]];
            expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        }
        $submission = formie()->submission($form)->with(['name' => 'Worker', 'email' => 'worker@example.test'])->save();
        if ($kind === 'integration') {
            Formie::$plugin->getIntegrationRunner()->queueSteps($submission, [$integration->handle], SubmissionOperation::SUBMIT, ['triggerEvent' => 'submit'], executionKey: $execution);
        } else {
            $notification = new Notification([
                'formId' => $form->id, 'name' => 'Worker notification', 'handle' => 'workerNotification',
                'to' => 'receiver@example.test', 'from' => 'sender@example.test', 'fromName' => 'Worker',
                'subject' => 'Worker recovery', 'content' => '<p>Stored answer: {field:name}</p>',
            ]);
            expect(Formie::$plugin->getNotifications()->saveNotification($notification))->toBeTrue();
            Formie::$plugin->getNotifications()->sendNotification($notification, $submission, true, $execution);
        }
        $uid = (new Query())->select('uid')->from(DeliveryAttempts::TABLE)->where([
            'submissionId' => $submission->id, 'executionUid' => $execution,
            'step' => $kind === 'integration' ? 'dispatch' : 'notification',
        ])->scalar();
        expect($jobId)->not->toBeNull();
        expect($uid)->toBeString();
        // Release any fixture project-config lock before a separate app boots.
        Craft::$app->getProjectConfig()->flush();
        $callback($jobId, $directory, $uid, $kind);
    } finally {
        $queue->off(\yii\queue\Queue::EVENT_AFTER_PUSH, $capture);
        $queue->ttr = $originalTtr;
        $queue->attempts = $originalAttempts;
        if ($jobId !== null) {
            $queue->release($jobId);
        }
        try {
            try {
                if ($form?->id) {
                    Craft::$app->getElements()->deleteElement($form, true);
                }
            } finally {
                if ($integration?->id) {
                    Formie::$plugin->getIntegrations()->deleteIntegration($integration);
                }
            }
        } finally {
            Event::off(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, $register);
            FileHelper::removeDirectory($directory);
        }
    }
}

it('completes a reserved delivery after its worker is killed and its reservation expires', function(string $kind): void {
    withDeliveryQueueFixture($kind, function(string $jobId, string $directory, string $uid) use ($kind): void {
        $worker = runDeliveryQueueWorker($jobId, $directory, 'interrupt');
        expect($worker['terminated'])->toBeTrue($worker['output']);
        expect($worker['signaled'])->toBeTrue()->and($worker['termsig'])->toBe(9);
        $queue = Craft::$app->getQueue();
        $row = (new Query())->from($queue->tableName)->where(['id' => $jobId])->one();
        expect((int)$row['ttr'])->toBe(2);
        expect((int)$row['attempt'])->toBe(1)->and((bool)$row['fail'])->toBeFalse();
        expect(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('sending');
        $initial = $kind === 'integration' ? ['contact', 'ticket'] : ['email'];
        expect(deliveryWorkerReceipts($directory))->toBe($initial);
        sleep((int)$row['ttr'] + 1);
        $restarted = runDeliveryQueueWorker($jobId, $directory, 'success');
        expect($restarted['exitcode'])->toBe(0, $restarted['output']);
        expect(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('succeeded');
        expect(deliveryWorkerReceipts($directory))->toBe([...$initial, $kind === 'integration' ? 'ticket' : 'email']);
        expect((new Query())->from($queue->tableName)->where(['id' => $jobId])->exists())->toBeFalse();
    });
})->with(['integration', 'notification']);

it('leaves an ordinary failed delivery for manual Retry while preserving completed steps', function(string $kind): void {
    withDeliveryQueueFixture($kind, function(string $jobId, string $directory, string $uid) use ($kind): void {
        runDeliveryQueueWorker($jobId, $directory, 'failure');
        $queue = Craft::$app->getQueue();
        $row = (new Query())->from($queue->tableName)->where(['id' => $jobId])->one();
        expect((bool)$row['fail'])->toBeTrue()->and($row['error'])->not->toBeEmpty();
        expect(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('failed');
        $initial = $kind === 'integration' ? ['contact', 'ticket'] : ['email'];
        expect(deliveryWorkerReceipts($directory))->toBe($initial);
        runDeliveryQueueWorker($jobId, $directory, 'success');
        expect(deliveryWorkerReceipts($directory))->toBe($initial);
        $queue->retry($jobId);
        $retried = runDeliveryQueueWorker($jobId, $directory, 'success');
        expect($retried['exitcode'])->toBe(0, $retried['output']);
        expect(deliveryWorkerReceipts($directory))->toBe([...$initial, $kind === 'integration' ? 'ticket' : 'email']);
        expect(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('succeeded');
    });
})->with(['integration', 'notification']);

it('completes an uninterrupted delivery through the queue worker', function(string $kind): void {
    withDeliveryQueueFixture($kind, function(string $jobId, string $directory, string $uid) use ($kind): void {
        $worker = runDeliveryQueueWorker($jobId, $directory, 'success');
        expect($worker['exitcode'])->toBe(0, $worker['output']);
        expect(deliveryWorkerReceipts($directory))->toBe($kind === 'integration' ? ['contact', 'ticket'] : ['email']);
        expect(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('succeeded');
    });
})->with(['integration', 'notification']);

it('honors a configured queue retry limit for delivery errors', function(string $kind, string $secondResult): void {
    withDeliveryQueueFixture($kind, function(string $jobId, string $directory, string $uid) use ($kind, $secondResult): void {
        runDeliveryQueueWorker($jobId, $directory, 'failure', 2);
        $queue = Craft::$app->getQueue();
        $row = (new Query())->from($queue->tableName)->where(['id' => $jobId])->one();
        expect((bool)$row['fail'])->toBeFalse()->and((int)$row['attempt'])->toBe(1);
        expect(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('failed');
        expect((int)$row['ttr'])->toBe(2);
        sleep((int)$row['ttr'] + 1);
        runDeliveryQueueWorker($jobId, $directory, $secondResult, 2);
        $steps = $kind === 'integration' ? ['contact', 'ticket', 'ticket'] : ['email', 'email'];
        expect(deliveryWorkerReceipts($directory))->toBe($steps);
        if ($secondResult === 'success') {
            expect(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('succeeded');
            expect((new Query())->from($queue->tableName)->where(['id' => $jobId])->exists())->toBeFalse();
        } else {
            $failed = (new Query())->from($queue->tableName)->where(['id' => $jobId])->one();
            expect((bool)$failed['fail'])->toBeTrue()->and((int)$failed['attempt'])->toBe(2);
            runDeliveryQueueWorker($jobId, $directory, 'success', 2);
            expect(deliveryWorkerReceipts($directory))->toBe($steps);
            expect(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('failed');
        }
    });
})->with(['integration', 'notification'])->with(['success', 'failure']);
