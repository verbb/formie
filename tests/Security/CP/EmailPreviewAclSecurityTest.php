<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use Tests\Support\UploadTestHelper;
use craft\elements\User;
use verbb\formie\controllers\EmailController;
use verbb\formie\Formie;
use verbb\formie\models\Notification;
use verbb\formie\services\Permissions;
use yii\base\Action;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

function notificationAssetAclFixture(): array
{
    $form = formie()
        ->form(['title' => 'Notification Asset ACL'])
        ->settings(['usePerFormPermissions' => true])
        ->create();
    $volume = UploadTestHelper::ensureUploadVolume();
    $asset = UploadTestHelper::seedAsset('notification-asset-' . bin2hex(random_bytes(4)) . '.txt', 'restricted attachment', $volume);
    $name = 'notificationAssetAcl' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);

    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());

    $permissions = [
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_ACCESS_FORMS,
        Permissions::PERM_MANAGE_FORMS . ':' . $form->uid,
        Formie::$plugin->getPermissions()->scopedPermission('formie-showNotifications', $form->uid),
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
    ];

    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $permissions))->toBeTrue();

    return compact('asset', 'form', 'permissions', 'user', 'volume');
}

it('requires a control panel request for email preview', function (): void {
    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        $request->setIsCpRequest(false);

        $controller = new EmailController('formie-email-security', Craft::$app);
        $controller->enableCsrfValidation = false;

        expect(fn() => $controller->beforeAction(new Action('preview', $controller)))
            ->toThrow(BadRequestHttpException::class, 'Request must be a control panel request');
    }, [
        'method' => 'POST',
        'requestUri' => '/actions/formie/email/preview',
        'bodyParams' => ['formId' => 1],
    ]);
})->group('security');

it('requires a control panel request for email test-send', function (): void {
    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        $request->setIsCpRequest(false);

        $controller = new EmailController('formie-email-security', Craft::$app);
        $controller->enableCsrfValidation = false;

        expect(fn() => $controller->beforeAction(new Action('send-test-email', $controller)))
            ->toThrow(BadRequestHttpException::class, 'Request must be a control panel request');
    }, [
        'method' => 'POST',
        'requestUri' => '/actions/formie/email/send-test-email',
        'bodyParams' => ['to' => 'attacker@example.test'],
    ]);
})->group('security');

it('denies form notification access for guests via email controller ACL helper', function (): void {
    $form = formie()
        ->form(['title' => 'Email Preview ACL'])
        ->singleLineTextField('fullName')
        ->create();

    Craft::$app->getUser()->setIdentity(null);

    $controller = new EmailController('formie-email-security', Craft::$app);
    $method = new ReflectionMethod(EmailController::class, '_requireFormNotificationAccess');
    $method->setAccessible(true);

    expect(fn() => $method->invoke($controller, $form))
        ->toThrow(ForbiddenHttpException::class);

    expect(Formie::$plugin->getPermissions()->canManageForm(null, $form))->toBeFalse();
})->group('security');

it('requires access settings for stencil email preview without formId', function (): void {
    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(null);

        $controller = new EmailController('formie-email-security', Craft::$app);
        $controller->enableCsrfValidation = false;

        expect(fn() => $controller->actionPreview())
            ->toThrow(ForbiddenHttpException::class);
    }, [
        'method' => 'POST',
        'requestUri' => '/admin/actions/formie/email/preview',
        'bodyParams' => [
            'isStencil' => '1',
            'handle' => 'missing-stencil-handle',
            'notification' => [
                'name' => 'Preview',
                'subject' => 'Hi',
                'to' => 'a@example.test',
                'from' => 'b@example.test',
                'content' => 'Body',
            ],
        ],
    ]);
})->group('security');

it('does not test-send assets the notification editor cannot view', function (): void {
    ['asset' => $asset, 'form' => $form, 'user' => $user] = notificationAssetAclFixture();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($asset, $form, $user): void {
        $request->setIsCpRequest(true);
        $identity = User::find()->id($user->id)->status(null)->one();
        Craft::$app->getUser()->setIdentity($identity);
        Craft::$app->set('sites', new \craft\services\Sites());

        expect(Craft::$app->getElements()->canView($asset, $identity))->toBeFalse();

        $controller = new EmailController('formie-email-security', Craft::$app);
        $controller->enableCsrfValidation = false;

        expect(fn() => $controller->actionSendTestEmail())
            ->toThrow(ForbiddenHttpException::class);
    }, [
        'method' => 'POST',
        'requestUri' => '/admin/actions/formie/email/send-test-email',
        'bodyParams' => [
            'formId' => $form->id,
            'to' => 'attacker@example.test',
            'notification' => [
                'name' => 'Restricted attachment test',
                'handle' => 'restrictedAttachmentTest',
                'subject' => 'Restricted attachment',
                'to' => 'recipient@example.test',
                'content' => 'Body',
                'attachAssets' => [['id' => $asset->id]],
            ],
        ],
    ]);
})->group('security');

it('validates attachment visibility for control panel saves without affecting trusted null-user paths', function (): void {
    ['asset' => $asset, 'form' => $form, 'permissions' => $permissions, 'user' => $user, 'volume' => $volume] = notificationAssetAclFixture();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($asset, $form, $permissions, $user, $volume): void {
        $request->setIsCpRequest(true);
        $identity = User::find()->id($user->id)->status(null)->one();
        Craft::$app->getUser()->setIdentity($identity);

        $notification = new Notification([
            'formId' => $form->id,
            'name' => 'Restricted attachment save',
            'handle' => 'restrictedAttachmentSave' . bin2hex(random_bytes(4)),
            'subject' => 'Restricted attachment',
            'to' => 'recipient@example.test',
            'attachAssets' => [['id' => $asset->id]],
        ]);

        expect($notification->validate())->toBeFalse()
            ->and($notification->getErrors('attachAssets'))->not->toBeEmpty()
            ->and(Formie::$plugin->getNotifications()->saveNotification($notification))->toBeFalse()
            ->and($notification->id)->toBeNull();

        $request->setIsCpRequest(false);
        $notification->clearErrors();

        expect($notification->validate())->toBeFalse();

        $request->setIsCpRequest(true);

        expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
            ...$permissions,
            'viewAssets:' . $volume->uid,
        ]))->toBeTrue();

        Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());

        expect(Craft::$app->getElements()->canView($asset, Craft::$app->getUser()->getIdentity()))->toBeFalse();

        expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
            ...$permissions,
            'viewAssets:' . $volume->uid,
            'viewPeerAssets:' . $volume->uid,
        ]))->toBeTrue();

        Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        $notification->clearErrors();

        expect(Craft::$app->getElements()->canView($asset, Craft::$app->getUser()->getIdentity()))->toBeTrue()
            ->and($notification->validate())->toBeTrue()
            ->and(Formie::$plugin->getNotifications()->saveNotification($notification))->toBeTrue()
            ->and($notification->id)->not->toBeNull();

        Craft::$app->getUser()->setIdentity(null);
        $trustedNotification = new Notification([
            'formId' => $form->id,
            'name' => 'Trusted attachment save',
            'handle' => 'trustedAttachmentSave' . bin2hex(random_bytes(4)),
            'subject' => 'Trusted attachment',
            'to' => 'recipient@example.test',
            'attachAssets' => [['id' => $asset->id]],
        ]);

        expect(Formie::$plugin->getNotifications()->saveNotification($trustedNotification))->toBeTrue()
            ->and($trustedNotification->id)->not->toBeNull();
    }, ['method' => 'POST']);
})->group('security');
