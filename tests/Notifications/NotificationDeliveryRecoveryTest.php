<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\models\Notification;
use verbb\formie\services\Emails;

it('retains the actual prepared email and submission projections before transport failure', function (): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->with(['fullName' => 'Original name'])->save();
    $notification = new Notification(['formId' => $form->id, 'name' => 'Diagnostic email', 'handle' => 'diagnosticEmail']);
    expect(Formie::$plugin->getNotifications()->saveNotification($notification, false))->toBeTrue();
    $original = Formie::$plugin->getEmails();
    $email = (new \craft\mail\Message())->setFrom('from@example.test')->setTo('person@example.test')->setSubject('Prepared subject')->setHtmlBody('<p>Prepared body</p>')->setTextBody('Prepared body');
    $emails = new class($email) extends Emails {
        public function __construct(private \craft\mail\Message $prepared) { parent::__construct(); }
        public function renderEmail(Notification $notification, \verbb\formie\elements\Submission $submission): array { return ['success' => true, 'email' => $this->prepared]; }
    };
    $cancel = static function ($event) { $event->isValid = false; };
    $mailer = Craft::$app->getMailer();
    $mailer->on(\yii\mail\BaseMailer::EVENT_BEFORE_SEND, $cancel);
    Formie::$plugin->set('emails', $emails);
    try {
        Formie::$plugin->getNotifications()->sendNotificationEmail($notification, $submission, null, 'prepared-email');
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $uid = (new \craft\db\Query())->select('uid')->from($attempts::TABLE)->where(['submissionId' => $submission->id, 'executionUid' => 'prepared-email'])->scalar();
        $bundle = json_encode($attempts->supportBundle($uid));
        expect($bundle)->toContain('Prepared subject', 'Prepared body', 'person@example.test', 'Original name', 'storedValues', 'textBody', 'email-response');
        $submission->setFieldValue('fullName', 'Later name');
        expect(json_encode($attempts->supportBundle($uid)))->not->toContain('Later name');
    } finally {
        $mailer->off(\yii\mail\BaseMailer::EVENT_BEFORE_SEND, $cancel);
        Formie::$plugin->set('emails', $original);
    }
});

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

it('sends a queued notification with current values before first send and on retry', function (bool $retry, string $change): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->with(['fullName' => 'Original name'])->save();
    $notification = new Notification(['id' => 510, 'uid' => 'prepared-notification', 'subject' => 'Original']);
    $original = Formie::$plugin->getEmails();
    $emails = new class extends Emails {
        public array $sent = [];
        public bool $fail = false;
        public function sendEmail(Notification $notification, \verbb\formie\elements\Submission $submission, mixed $queueJob = null, bool $createSentNotification = true): array {
            $this->sent[] = [$notification->subject, $submission->getFieldValue('fullName')];
            return $this->fail ? ['error' => 'Synthetic known failure'] : ['success' => true];
        }
    };
    Formie::$plugin->set('emails', $emails);
    try {
        $service = Formie::$plugin->getNotifications();
        $service->sendNotification($notification, $submission, true, 'prepared-operation');
        $row = (new \craft\db\Query())->from(\verbb\formie\services\DeliveryAttempts::TABLE)->where(['submissionId' => $submission->id, 'executionUid' => 'prepared-operation', 'step' => 'notification'])->one();
        $job = new \verbb\formie\jobs\SendNotification(['deliveryAttemptUid' => $row['uid']]);
        if ($retry) {
            $emails->fail = true;
            $service->sendNotificationEmail($notification, $submission, $job, 'prepared-operation');
            $emails->fail = false;
        }
        if ($change === 'content') {
            $submission->setFieldValue('fullName', 'Changed after enqueue');
            expect(Craft::$app->getElements()->saveElement($submission, false))->toBeTrue();
        } else {
            $notification->subject = 'Changed after enqueue';
        }
        $result = $service->sendNotificationEmail($notification, $submission, $job, 'prepared-operation');
        $current = $change === 'content' ? ['Original', 'Changed after enqueue'] : ['Changed after enqueue', 'Original name'];
        expect($result['success'])->toBeTrue()
            ->and($emails->sent)->toBe($retry ? [['Original', 'Original name'], $current] : [$current]);
    } finally { Formie::$plugin->set('emails', $original); }
})->with([[false, 'content'], [true, 'content'], [false, 'notification'], [true, 'notification']]);

it('sends a failed queued notification again when its job is retried, then remembers success', function (): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->save();
    $notification = new Notification(['formId' => $form->id, 'name' => 'Retried email', 'handle' => 'retriedEmail']);
    expect(Formie::$plugin->getNotifications()->saveNotification($notification, false))->toBeTrue();
    $original = Formie::$plugin->getEmails();
    $emails = new class extends Emails {
        public int $calls = 0;
        public bool $fail = true;
        public function sendEmail(Notification $notification, \verbb\formie\elements\Submission $submission, mixed $queueJob = null, bool $createSentNotification = true): array
        {
            $this->calls++;
            return $this->fail ? ['error' => 'Connection closed'] : ['success' => true];
        }
    };
    Formie::$plugin->set('emails', $emails);
    try {
        Formie::$plugin->getNotifications()->sendNotification($notification, $submission, true, 'retried-job');
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $uid = (new \craft\db\Query())->select('uid')->from($attempts::TABLE)->where(['submissionId' => $submission->id, 'executionUid' => 'retried-job'])->scalar();
        $job = new \verbb\formie\jobs\SendNotification(['deliveryAttemptUid' => $uid]);
        $execute = fn() => $job->execute(Craft::$app->getQueue());
        expect($execute)->toThrow(RuntimeException::class, 'Notification delivery failed.');
        expect($attempts->get($uid)['status'])->toBe('failed');
        expect($execute)->toThrow(RuntimeException::class, 'Notification delivery failed.');
        expect($emails->calls)->toBe(2);
        $emails->fail = false;
        $execute();
        $execute();
        expect($emails->calls)->toBe(3)->and($attempts->get($uid)['status'])->toBe('succeeded');
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

it('does not hold a new notification identity back after an earlier failure', function (): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->save();
    $notification = new Notification(['id' => 506, 'uid' => 'retried-notification']);
    $original = Formie::$plugin->getEmails();
    $emails = new class extends Emails {
        public int $calls = 0;
        public function sendEmail(Notification $notification, \verbb\formie\elements\Submission $submission, mixed $queueJob = null, bool $createSentNotification = true): array {
            $this->calls++;
            return $this->calls === 1 ? ['error' => 'Connection closed'] : ['success' => true];
        }
    };
    Formie::$plugin->set('emails', $emails);
    try {
        $service = Formie::$plugin->getNotifications();
        expect($service->sendNotificationEmail($notification, $submission, null, 'original')['success'])->toBeFalse();
        expect($service->sendNotificationEmail($notification, $submission, null, 'new')['success'])->toBeTrue();
        expect($emails->calls)->toBe(2);
        expect($service->sendNotificationEmail($notification, $submission, null, 'original')['success'])->toBeTrue();
        expect($emails->calls)->toBe(3);
    } finally {
        Formie::$plugin->set('emails', $original);
    }
});

it('sends a notification again after an uncertain legacy receipt', function (): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->save();
    $notification = new Notification(['id' => 507, 'uid' => 'legacy-uncertain-notification']);
    $legacy = new \verbb\formie\helpers\DeliveryAttempt($submission->id, 'notification-send:' . $notification->uid, 'legacy-job');
    try { $legacy->execute([], fn() => throw new RuntimeException('Response lost')); } catch (Throwable) {}
    $original = Formie::$plugin->getEmails();
    $emails = new class extends Emails {
        public int $calls = 0;
        public function sendEmail(Notification $notification, \verbb\formie\elements\Submission $submission, mixed $queueJob = null, bool $createSentNotification = true): array { $this->calls++; return ['success' => true]; }
    };
    Formie::$plugin->set('emails', $emails);
    try {
        $service = Formie::$plugin->getNotifications();
        expect($service->sendNotificationEmail($notification, $submission, null, 'legacy-job')['success'])->toBeTrue();
        expect($emails->calls)->toBe(1);
    } finally {
        Formie::$plugin->set('emails', $original);
    }
});
