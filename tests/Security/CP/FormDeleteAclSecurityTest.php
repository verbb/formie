<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use craft\helpers\UrlHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FormsController;
use verbb\formie\Formie;
use verbb\formie\services\Permissions;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;

function formDeleteAclFixture(bool $grantDeletePermission = true): array
{
    $allowed = formie()->form(['title' => 'Allowed Form Deletion'])
        ->settings(['usePerFormPermissions' => true])
        ->create();
    $restricted = formie()->form(['title' => 'Restricted Form Deletion'])
        ->settings(['usePerFormPermissions' => true])
        ->create();
    $name = 'formDeleteAcl' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);

    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());

    $permissions = [
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_ACCESS_FORMS,
        Permissions::PERM_MANAGE_FORMS . ':' . $allowed->uid,
        Formie::$plugin->getPermissions()->scopedPermission(
            Permissions::PERM_VIEW_FORMS,
            Formie::$plugin->getPermissions()->groupScope(Permissions::GROUP_UNGROUPED),
        ),
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
    ];

    if ($grantDeletePermission) {
        $permissions[] = Permissions::PERM_DELETE_FORMS;
    }

    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $permissions))->toBeTrue();

    return compact('allowed', 'restricted', 'user');
}

function formDeleteAclDateDeleted(int $elementId): mixed
{
    return (new Query())
        ->select('dateDeleted')
        ->from('{{%elements}}')
        ->where(['id' => $elementId])
        ->scalar();
}

it('requires management access to the exact form before deletion', function (): void {
    ['allowed' => $allowed, 'restricted' => $restricted, 'user' => $user] = formDeleteAclFixture();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($restricted, $user): void {
        $request->setIsCpRequest(true);
        $identity = User::find()->id($user->id)->status(null)->one();
        Craft::$app->getUser()->setIdentity($identity);
        Craft::$app->set('sites', new \craft\services\Sites());
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'formId' => $restricted->id,
        ]);

        expect(Formie::$plugin->getPermissions()->canViewForm($identity, $restricted))->toBeTrue()
            ->and(Formie::$plugin->getPermissions()->canManageForm($identity, $restricted))->toBeFalse()
            ->and(Formie::$plugin->getPermissions()->canDeleteForm($identity, $restricted))->toBeFalse();

        expect(fn() => (new FormsController('forms', Formie::$plugin))->runAction('delete-form'))
            ->toThrow(ForbiddenHttpException::class, 'User is not permitted to perform this action');
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);

    expect(formDeleteAclDateDeleted((int)$allowed->id))->toBeNull()
        ->and(formDeleteAclDateDeleted((int)$restricted->id))->toBeNull();
})->group('security');

it('allows deletion when the user can manage the exact form', function (): void {
    ['allowed' => $allowed, 'restricted' => $restricted, 'user' => $user] = formDeleteAclFixture();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($allowed, $user): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        Craft::$app->set('sites', new \craft\services\Sites());
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'formId' => (string)$allowed->id,
        ]);

        $response = (new FormsController('forms', Formie::$plugin))->runAction('delete-form');

        expect($response->data)->toBe([
            'success' => true,
            'redirect' => UrlHelper::cpUrl('formie/forms'),
        ]);
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);

    expect(formDeleteAclDateDeleted((int)$allowed->id))->not->toBeNull()
        ->and(formDeleteAclDateDeleted((int)$restricted->id))->toBeNull();
})->group('security');

it('allows deletion when the user can manage the form group', function (): void {
    ['allowed' => $allowed, 'restricted' => $restricted, 'user' => $user] = formDeleteAclFixture();

    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_ACCESS_FORMS,
        Permissions::PERM_DELETE_FORMS,
        Formie::$plugin->getPermissions()->scopedPermission(
            Permissions::PERM_MANAGE_FORMS,
            Formie::$plugin->getPermissions()->groupScope(Permissions::GROUP_UNGROUPED),
        ),
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
    ]))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($allowed, $user): void {
        $request->setIsCpRequest(true);
        $identity = User::find()->id($user->id)->status(null)->one();
        Craft::$app->getUser()->setIdentity($identity);
        Craft::$app->set('sites', new \craft\services\Sites());
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'formId' => $allowed->id,
        ]);

        expect(Formie::$plugin->getPermissions()->canDeleteForm($identity, $allowed))->toBeTrue()
            ->and((new FormsController('forms', Formie::$plugin))->runAction('delete-form')->data['success'])->toBeTrue();
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);

    expect(formDeleteAclDateDeleted((int)$allowed->id))->not->toBeNull()
        ->and(formDeleteAclDateDeleted((int)$restricted->id))->toBeNull();
})->group('security');

it('allows administrators to delete forms', function (): void {
    ['allowed' => $allowed, 'restricted' => $restricted] = formDeleteAclFixture();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($allowed): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'formId' => $allowed->id,
        ]);

        expect((new FormsController('forms', Formie::$plugin))->runAction('delete-form')->data['success'])->toBeTrue();
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);

    expect(formDeleteAclDateDeleted((int)$allowed->id))->not->toBeNull()
        ->and(formDeleteAclDateDeleted((int)$restricted->id))->toBeNull();
})->group('security');

it('preserves the global delete permission requirement', function (): void {
    ['allowed' => $allowed, 'user' => $user] = formDeleteAclFixture(false);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($allowed, $user): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        Craft::$app->set('sites', new \craft\services\Sites());
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'formId' => $allowed->id,
        ]);

        expect(fn() => (new FormsController('forms', Formie::$plugin))->runAction('delete-form'))
            ->toThrow(ForbiddenHttpException::class);
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);

    expect(formDeleteAclDateDeleted((int)$allowed->id))->toBeNull();
})->group('security');

it('preserves delete form request and not-found contracts', function (): void {
    ['allowed' => $allowed] = formDeleteAclFixture();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($allowed): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());

        expect(fn() => (new FormsController('forms', Formie::$plugin))->runAction('delete-form'))
            ->toThrow(MethodNotAllowedHttpException::class, 'Post request required');
    }, ['method' => 'GET', 'queryParams' => ['formId' => $allowed->id]]);

    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'formId' => 999999999,
        ]);

        expect(fn() => (new FormsController('forms', Formie::$plugin))->runAction('delete-form'))
            ->toThrow(NotFoundHttpException::class, 'Form not found');
    }, ['method' => 'POST']);

    expect(formDeleteAclDateDeleted((int)$allowed->id))->toBeNull();
})->group('security');
