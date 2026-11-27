<?php

declare(strict_types=1);

use craft\elements\User;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\elements\SentNotification;
use verbb\formie\Formie;
use verbb\formie\models\Notification;

it('reports the actual result of manually sending a submission notification', function (bool $failTransport): void {
    $form = formie()->form()->singleLineTextField('answer')->create();
    $submission = formie()->submission($form)->with(['answer' => 'Ordinary enquiry'])->save();
    $notification = new Notification([
        'formId' => $form->id, 'name' => 'Manual notice', 'handle' => 'manualNotice',
        'to' => 'recipient@example.test', 'from' => 'sender@example.test',
        'subject' => 'Manual notice', 'content' => '<p>Ordinary enquiry</p>',
    ]);
    expect(Formie::$plugin->getNotifications()->saveNotification($notification))->toBeTrue();
    $settings = Formie::$plugin->getSettings();
    $previous = $settings->sentNotifications;
    $settings->sentNotifications = true;

    try {
        WebRequestTestHelper::withWebRequestContext(function ($request) use ($failTransport): void {
            $request->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
            $mailer = Craft::$app->getMailer();
            $mailer->useFileTransport = !$failTransport;

            if ($failTransport) {
                $mailer->setTransport(new EsmtpTransport('127.0.0.1', 9, false));
            }

            $response = (new SubmissionsController('submissions', Formie::$plugin))->actionSendNotification();
            expect((bool)($response->data['success'] ?? false))->toBe(!$failTransport)
                ->and($response->statusCode)->toBe($failTransport ? 400 : 200);
            $flashes = Craft::$app->getSession()->getAllFlashes();

            if ($failTransport) {
                expect($flashes)->not->toHaveKey('cp-notification-success')
                    ->and($response->data['message'])->toBeString()->not->toBe('');
            } else {
                expect($flashes)->toHaveKey('cp-notification-success');
            }
        }, [
            'method' => 'POST', 'headers' => ['Accept' => 'application/json'],
            'bodyParams' => ['submissionId' => $submission->id, 'notificationId' => $notification->id],
        ]);
        $sent = SentNotification::find()->submissionId($submission->id)->orderBy(['id' => SORT_DESC])->one();
        expect($sent)->not->toBeNull()->and($sent->success)->toBe(!$failTransport);
    } finally {
        $settings->sentNotifications = $previous;
    }
})->with([false, true]);
