<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\SentNotificationsController;
use verbb\formie\elements\SentNotification;
use verbb\formie\Formie;
use verbb\formie\models\Notification;

it('returns structured resend dialog data for the Plugin Kit modal', function (): void {
    $form = formie()->form()->create();
    $source = new SentNotification([
        'title' => 'Stored notice',
        'formId' => (string)$form->id,
        'subject' => 'Stored subject',
        'to' => 'alerts@example.test',
        'cc' => 'copy@example.test',
        'from' => 'sender@example.test',
        'fromName' => 'Sender',
        'body' => 'Stored content',
        'htmlBody' => '<p>Stored content</p>',
        'success' => true,
    ]);
    expect(Craft::$app->getElements()->saveElement($source))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function () use ($source): void {
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        $controller = new SentNotificationsController('sent-notifications', Formie::$plugin);
        $response = $controller->actionGetResendModalData();

        expect($response->data)
            ->success->toBeTrue()
            ->notification->toMatchArray([
                'id' => $source->id,
                'to' => 'alerts@example.test',
                'cc' => 'copy@example.test',
                'subject' => 'Stored subject',
                'from' => 'sender@example.test',
                'fromName' => 'Sender',
                'body' => 'Stored content',
                'htmlBody' => '<p>Stored content</p>',
            ]);
    }, [
        'method' => 'POST',
        'headers' => ['Accept' => 'application/json'],
        'bodyParams' => ['id' => $source->id],
    ]);
});

it('resends stored messages independently of the current source records', function (string $state, bool $bulk): void {
    $form = formie()->form()->singleLineTextField('answer')->create();
    $submission = formie()->submission($form)->with(['answer' => 'Original'])->save();
    $notification = new Notification(['formId' => $form->id, 'name' => 'Original notice', 'handle' => 'notice', 'subject' => 'Original subject']);
    expect(Formie::$plugin->getNotifications()->saveNotification($notification, false))->toBeTrue();
    $source = new SentNotification([
        'title' => 'Original notice', 'formId' => (string)$form->id, 'submissionId' => (string)$submission->id,
        'notificationId' => (string)$notification->id, 'subject' => 'Original subject',
        'to' => 'original@example.test', 'from' => 'sender@example.test', 'fromName' => 'Sender',
        'body' => 'Original content', 'htmlBody' => '<p>Original content</p>', 'success' => true,
    ]);
    expect(Craft::$app->getElements()->saveElement($source))->toBeTrue();
    if ($state === 'deleted submission') {
        expect(Craft::$app->getElements()->deleteElement($submission))->toBeTrue();
    } elseif ($state === 'deleted notification') {
        expect(Formie::$plugin->getNotifications()->deleteNotification($notification))->toBeTrue();
    } else {
        $submission->isSpam = $state === 'spam';
        $submission->isIncomplete = $state === 'incomplete';
        expect(Craft::$app->getElements()->saveElement($submission, false))->toBeTrue();
    }
    $settings = Formie::$plugin->getSettings();
    $originalSetting = $settings->sentNotifications;
    $settings->sentNotifications = true;
    try {
        WebRequestTestHelper::withWebRequestContext(function () use ($source, $bulk): void {
            Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
            Craft::$app->getMailer()->useFileTransport = true;
            $controller = new SentNotificationsController('sent-notifications', Formie::$plugin);
            $response = $bulk ? $controller->actionBulkResend() : $controller->actionResend();
            expect($response->data['success'])->toBeTrue();
        }, [
            'method' => 'POST', 'headers' => ['Accept' => 'application/json'],
            'bodyParams' => ['id' => $source->id, 'ids' => [$source->id], 'to' => 'replacement@example.test', 'recipientsType' => 'custom'],
        ]);
        $resent = SentNotification::find()->formId($form->id)->orderBy(['id' => SORT_DESC])->one();
        expect($resent->id)->not->toBe($source->id)
            ->and($resent->to)->toBe('replacement@example.test')
            ->and($resent->subject)->toBe('Original subject')
            ->and($resent->htmlBody)->toBe('<p>Original content</p>')
            ->and($resent->success)->toBeTrue();
        if (in_array($state, ['spam', 'incomplete'], true)) {
            expect($resent->getSubmission()?->id)->toBe($submission->id);
        }
    } finally {
        $settings->sentNotifications = $originalSetting;
    }
})->with(['spam', 'incomplete', 'deleted submission', 'deleted notification'])->with([false, true]);
