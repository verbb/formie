<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\Formie;
use verbb\formie\models\Notification;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;

/**
 * CP side-effect actions (send notification / run integration) must require an
 * authenticated Formie-capable user and bind notifications to the submission's form.
 *
 * Note: Craft Solo (this test edition) makes User::can() always true, so permission
 * denial is asserted via guest identity (null), not a non-admin User element.
 */

function submissionSideEffectFixture(): array
{
    $form = formie()
        ->form(['title' => 'Side Effect ACL'])
        ->singleLineTextField('fullName')
        ->create();

    $notification = new Notification([
        'name' => 'Admin Notification',
        'handle' => 'adminNotification' . uniqid(),
        'enabled' => true,
        'subject' => 'New submission',
        'to' => 'admin@example.test',
        'from' => 'from@example.test',
        'content' => 'Hello',
    ]);

    $form->setNotifications([$notification]);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $form = Formie::$plugin->getForms()->getFormById((int)$form->id);
    $notifications = $form->getNotifications();
    expect($notifications)->not->toBeEmpty();

    $submission = formie()->submission($form)->with(['fullName' => 'Test User'])->save();

    return [
        'form' => $form,
        'notification' => $notifications[0],
        'submission' => $submission,
    ];
}

it('requires post for send-notification', function (): void {
    ['notification' => $notification, 'submission' => $submission] = submissionSideEffectFixture();

    WebRequestTestHelper::withWebRequestContext(function () use ($notification, $submission): void {
        $controller = new SubmissionsController('formie-submissions-side-effect', Craft::$app);

        expect(fn() => $controller->actionSendNotification())
            ->toThrow(MethodNotAllowedHttpException::class);
    }, [
        'method' => 'GET',
        'headers' => ['Accept' => 'application/json'],
        'queryParams' => [
            'notificationId' => (string)$notification->id,
            'submissionId' => (string)$submission->id,
        ],
    ]);
})->group('security');

it('forbids guests from send-notification', function (): void {
    ['notification' => $notification, 'submission' => $submission] = submissionSideEffectFixture();

    WebRequestTestHelper::withWebRequestContext(function () use ($notification, $submission): void {
        Craft::$app->getUser()->setIdentity(null);

        $controller = new SubmissionsController('formie-submissions-side-effect', Craft::$app);

        expect(fn() => $controller->actionSendNotification())
            ->toThrow(ForbiddenHttpException::class);
    }, [
        'method' => 'POST',
        'headers' => ['Accept' => 'application/json'],
        'bodyParams' => [
            'notificationId' => (string)$notification->id,
            'submissionId' => (string)$submission->id,
        ],
    ]);
})->group('security');

it('rejects send-notification when notification belongs to another form', function (): void {
    ['submission' => $submission] = submissionSideEffectFixture();

    $otherForm = formie()
        ->form(['title' => 'Other Form Notification'])
        ->singleLineTextField('fullName')
        ->create();

    $foreign = new Notification([
        'name' => 'Foreign Notification',
        'handle' => 'foreignNotification' . uniqid(),
        'enabled' => true,
        'subject' => 'Hijack',
        'to' => 'attacker@example.test',
        'from' => 'from@example.test',
        'content' => 'Nope',
    ]);
    $otherForm->setNotifications([$foreign]);
    expect(Craft::$app->getElements()->saveElement($otherForm))->toBeTrue();

    $otherForm = Formie::$plugin->getForms()->getFormById((int)$otherForm->id);
    $foreignNotification = $otherForm->getNotifications()[0];

    expect((int)$foreignNotification->formId)->not->toBe((int)$submission->formId);

    WebRequestTestHelper::withWebRequestContext(function () use ($foreignNotification, $submission): void {
        // Solo edition: any authenticated user passes User::can(); formId binding is the gate.
        $admin = \craft\elements\User::find()->admin(true)->one()
            ?? (function (): \craft\elements\User {
                $user = new \craft\elements\User();
                $user->admin = true;
                return $user;
            })();
        Craft::$app->getUser()->setIdentity($admin);

        $controller = new SubmissionsController('formie-submissions-side-effect', Craft::$app);
        $response = $controller->actionSendNotification();

        // Craft asFailure() returns 400 with `message` (no success:false key).
        expect($response->statusCode)->toBe(400)
            ->and((string)($response->data['message'] ?? ''))->toContain('Notification not found');
    }, [
        'method' => 'POST',
        'headers' => ['Accept' => 'application/json'],
        'bodyParams' => [
            'notificationId' => (string)$foreignNotification->id,
            'submissionId' => (string)$submission->id,
        ],
    ]);
})->group('security');

it('forbids guests from run-integration', function (): void {
    ['submission' => $submission] = submissionSideEffectFixture();

    WebRequestTestHelper::withWebRequestContext(function () use ($submission): void {
        Craft::$app->getUser()->setIdentity(null);

        $controller = new SubmissionsController('formie-submissions-side-effect', Craft::$app);

        expect(fn() => $controller->actionRunIntegration())
            ->toThrow(ForbiddenHttpException::class);
    }, [
        'method' => 'POST',
        'headers' => ['Accept' => 'application/json'],
        'bodyParams' => [
            'integrationId' => '1',
            'submissionId' => (string)$submission->id,
        ],
    ]);
})->group('security');

it('forbids guests from send-notification modal content', function (): void {
    ['submission' => $submission] = submissionSideEffectFixture();

    WebRequestTestHelper::withWebRequestContext(function () use ($submission): void {
        Craft::$app->getUser()->setIdentity(null);

        $controller = new SubmissionsController('formie-submissions-side-effect', Craft::$app);

        expect(fn() => $controller->actionGetSendNotificationModalContent())
            ->toThrow(ForbiddenHttpException::class);
    }, [
        'method' => 'POST',
        'headers' => ['Accept' => 'application/json'],
        'bodyParams' => [
            'id' => (string)$submission->id,
        ],
    ]);
})->group('security');

it('returns not found for send-notification modal when submission is missing', function (): void {
    WebRequestTestHelper::withWebRequestContext(function (): void {
        $admin = \craft\elements\User::find()->admin(true)->one()
            ?? (function (): \craft\elements\User {
                $user = new \craft\elements\User();
                $user->admin = true;
                return $user;
            })();
        Craft::$app->getUser()->setIdentity($admin);

        $controller = new SubmissionsController('formie-submissions-side-effect', Craft::$app);

        expect(fn() => $controller->actionGetSendNotificationModalContent())
            ->toThrow(NotFoundHttpException::class);
    }, [
        'method' => 'POST',
        'headers' => ['Accept' => 'application/json'],
        'bodyParams' => [
            'id' => '999999999',
        ],
    ]);
})->group('security');
