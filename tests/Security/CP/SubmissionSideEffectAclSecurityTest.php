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

 */

function submissionSideEffectFixture(): array
{
    $form = formie()
        ->form(['title' => 'Side Effect ACL'])
        ->settings(['usePerFormPermissions' => true])
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

it('enforces persisted user permissions for one form through the notification modal action', function (): void {
    $allowed = submissionSideEffectFixture();
    $denied = submissionSideEffectFixture();
    $user = new \craft\elements\User(['username' => 'acl' . uniqid(), 'email' => 'acl' . uniqid() . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    $permissions = Formie::$plugin->getPermissions();
    $scopedPermission = $permissions->scopedPermission(\verbb\formie\services\Permissions::PERM_VIEW_SUBMISSIONS, $permissions->formScope($allowed['form']));
    // Register the forms created by this fixture before saving their scoped grants.
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp', 'accessPlugin-formie',
        \verbb\formie\services\Permissions::PERM_ACCESS_SUBMISSIONS,
        $scopedPermission,
    ]))->toBeTrue();
    expect(Craft::$app->getUserPermissions()->getPermissionsByUserId($user->id))->toContain(strtolower($scopedPermission));
    WebRequestTestHelper::withWebRequestContext(function ($request) use ($user, $allowed, $denied): void {
        $request->setIsCpRequest(true);
        Craft::$app->getView()->setTemplateMode(\craft\web\View::TEMPLATE_MODE_CP);
        Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->id($user->id)->one());
        $csrf = [$request->csrfParam => $request->getCsrfToken()];
        $request->setBodyParams($csrf + ['id' => $denied['submission']->id]);
        expect(fn() => (new SubmissionsController('submissions', Formie::$plugin))->runAction('get-send-notification-modal-content'))
            ->toThrow(ForbiddenHttpException::class, 'User is not permitted');
        $request->setBodyParams($csrf + ['id' => $allowed['submission']->id]);
        $result = (new SubmissionsController('submissions', Formie::$plugin))->runAction('get-send-notification-modal-content');
        expect($result->data['success'])->toBeTrue()
            ->and($result->data['modalHtml'])->toContain('Send Email Notification', 'value="' . $allowed['submission']->id . '"');
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);
})->group('security');
