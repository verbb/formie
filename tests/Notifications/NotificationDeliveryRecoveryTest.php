<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\models\Notification;
use verbb\formie\services\Emails;

it('checkpoints individual notifications and retries only the failed notification', function (): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->with(['fullName' => 'Notifications'])->save();
    $first = new Notification(['id' => 501, 'uid' => 'notification-first', 'handle' => 'first']);
    $second = new Notification(['id' => 502, 'uid' => 'notification-second', 'handle' => 'second']);
    $original = Formie::$plugin->getEmails();
    $emails = new class extends Emails {
        public array $calls = [];
        public bool $fail = true;
        public function sendEmail(Notification $notification, \verbb\formie\elements\Submission $submission, mixed $queueJob = null, bool $createSentNotification = true): array
        {
            $this->calls[] = $notification->handle;
            return $notification->handle === 'second' && $this->fail ? ['error' => 'Template unavailable'] : ['success' => true];
        }
    };
    Formie::$plugin->set('emails', $emails);
    try {
        $service = Formie::$plugin->getNotifications();
        $service->sendNotification($first, $submission, false, 'batch');
        expect(fn() => $service->sendNotification($second, $submission, false, 'batch'))->toThrow(RuntimeException::class);
        $emails->fail = false;
        $service->sendNotification($first, $submission, false, 'batch');
        $service->sendNotification($second, $submission, false, 'batch');
        expect($emails->calls)->toBe(['first', 'second', 'second']);
    } finally {
        Formie::$plugin->set('emails', $original);
    }
});

it('stops automatic notification retry when the mailer outcome is unknown', function (): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->save();
    $notification = new Notification(['id' => 503, 'uid' => 'notification-uncertain']);
    $original = Formie::$plugin->getEmails();
    $emails = new class extends Emails {
        public int $calls = 0;
        public function sendEmail(Notification $notification, \verbb\formie\elements\Submission $submission, mixed $queueJob = null, bool $createSentNotification = true): array
        {
            $this->calls++;
            return ['error' => 'Connection closed', 'deliveryOutcomeUnknown' => true];
        }
    };
    Formie::$plugin->set('emails', $emails);
    try {
        $send = fn() => Formie::$plugin->getNotifications()->sendNotification($notification, $submission, false, 'uncertain');
        expect($send)->toThrow(RuntimeException::class, 'outcome unknown');
        expect($send)->toThrow(RuntimeException::class, 'outcome unknown');
        expect($emails->calls)->toBe(1);
    } finally {
        Formie::$plugin->set('emails', $original);
    }
});

it('queues notifications atomically with an element transaction even when synchronous delivery is configured', function (): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->save();
    $notification = new Notification(['id' => 505, 'uid' => 'transaction-notification']);
    $db = Craft::$app->getDb();
    $queue = Craft::$app->getQueue();
    $count = fn() => (int)(new \craft\db\Query())->from($queue->tableName)->count();
    $before = $count();
    $transaction = $db->beginTransaction();
    try {
        Formie::$plugin->getNotifications()->sendNotification($notification, $submission, false, 'transaction-fixture');
        expect($count())->toBe($before + 1);
    } finally {
        $transaction->rollBack();
    }
    expect($count())->toBe($before);
});
