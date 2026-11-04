<?php

declare(strict_types=1);

use craft\db\Query;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\Table;
use verbb\formie\jobs\SendNotification;
use verbb\formie\models\HtmlSubmissionRequest;
use verbb\formie\models\Notification;
use verbb\formie\services\Emails;

// A notification failing can't undo a saved submission, so the visitor must never see an error for one.
function failedNotificationForm(): \verbb\formie\elements\Form
{
    $form = formie()->form(['title' => 'Failed notification'])->singleLineTextField('fullName')->create();

    $notifications = array_map(static fn(string $handle) => new Notification([
        'formId' => $form->id,
        'name' => $handle,
        'handle' => $handle,
        'enabled' => true,
        'subject' => $handle,
        'to' => 'staff@example.test',
    ]), ['failing', 'working']);

    foreach ($notifications as $notification) {
        expect(Formie::$plugin->getNotifications()->saveNotification($notification))->toBeTrue();
    }

    return \verbb\formie\elements\Form::find()->id($form->id)->one();
}

function failedNotificationSubmit(\verbb\formie\elements\Form $form): mixed
{
    return WebRequestTestHelper::withWebRequestContext(static function () use ($form) {
        return runHtmlSubmission(new HtmlSubmissionRequest([
            'handle' => $form->handle,
            'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT,
            'submitAction' => 'submit',
            'pageId' => (int)$form->getPages()[0]->id,
            'fieldParamNamespace' => 'fields',
        ]));
    }, ['method' => 'POST', 'bodyParams' => ['fields' => ['fullName' => 'Visitor']]]);
}

function failedNotificationEmails(): Emails
{
    return new class extends Emails {
        public array $calls = [];

        public function sendEmail(Notification $notification, Submission $submission, mixed $queueJob = null, bool $createSentNotification = true): array
        {
            $this->calls[] = $notification->handle;

            return $notification->handle === 'failing' ? ['error' => 'Reference resolution failed: missingField.'] : ['success' => true];
        }
    };
}

function failedNotificationScenario(bool $useQueue, callable $assertions): void
{
    $settings = Formie::$plugin->getSettings();
    $previousQueue = $settings->useQueueForNotifications;
    $originalEmails = Formie::$plugin->getEmails();
    $emails = failedNotificationEmails();
    $settings->useQueueForNotifications = $useQueue;
    Formie::$plugin->set('emails', $emails);

    try {
        $assertions($emails);
    } finally {
        $settings->useQueueForNotifications = $previousQueue;
        Formie::$plugin->set('emails', $originalEmails);
    }
}

it('completes the submission and sends the remaining notifications without the queue', function (): void {
    failedNotificationScenario(false, function (Emails $emails): void {
        $form = failedNotificationForm();
        $response = failedNotificationSubmit($form);
        $saved = Submission::find()->formId($form->id)->one();

        expect($saved)->not->toBeNull()
            ->and($response)->not->toBeNull()
            ->and($emails->calls)->toBe(['failing', 'working']);
    });
});

it('keeps notifications in the order they were added when they share a creation time', function (): void {
    $form = failedNotificationForm();
    $db = Craft::$app->getDb();
    $db->createCommand()->update(Table::FORMIE_NOTIFICATIONS, ['dateCreated' => '2026-01-01 00:00:00'], ['formId' => $form->id])->execute();

    // Store the first row physically after the second, which PostgreSQL then returns first unless told otherwise.
    $first = (new Query())->from(Table::FORMIE_NOTIFICATIONS)->where(['formId' => $form->id, 'handle' => 'failing'])->one();
    $db->createCommand()->delete(Table::FORMIE_NOTIFICATIONS, ['id' => $first['id']])->execute();
    $db->createCommand()->insert(Table::FORMIE_NOTIFICATIONS, $first, false)->execute();

    $notifications = Formie::$plugin->getNotifications()->getFormNotifications($form);

    expect(array_map(static fn(Notification $notification) => $notification->handle, $notifications))->toBe(['failing', 'working']);
});

it('records a failed notification for delivery diagnostics without the queue', function (): void {
    failedNotificationScenario(false, function (): void {
        $form = failedNotificationForm();
        failedNotificationSubmit($form);
        $saved = Submission::find()->formId($form->id)->one();
        $failing = array_values(array_filter($form->getNotifications(), static fn($n) => $n->handle === 'failing'))[0];

        $attempt = (new Query())
            ->from('{{%formie_delivery_attempts}}')
            ->where(['submissionId' => $saved->id, 'binding' => 'notification:' . $failing->id])
            ->one();

        expect($attempt)->not->toBeFalse()
            ->and((string)($attempt['status'] ?? ''))->not->toBe('succeeded');
    });
});

it('completes the submission and leaves notifications to the queue when it is on', function (): void {
    failedNotificationScenario(true, function (Emails $emails): void {
        $form = failedNotificationForm();
        $response = failedNotificationSubmit($form);
        $saved = Submission::find()->formId($form->id)->one();

        expect($saved)->not->toBeNull()
            ->and($response)->not->toBeNull()
            ->and($emails->calls)->toBe([]);
    });
});

it('still reports a failure to whoever resends a single notification', function (): void {
    failedNotificationScenario(false, function (): void {
        $form = failedNotificationForm();
        $submission = formie()->submission($form)->with(['fullName' => 'Admin'])->save();
        $failing = array_values(array_filter($form->getNotifications(), static fn($n) => $n->handle === 'failing'))[0];

        expect(fn() => Formie::$plugin->getNotifications()->sendNotification($failing, $submission, false, 'resend'))
            ->toThrow(RuntimeException::class);
    });
});
