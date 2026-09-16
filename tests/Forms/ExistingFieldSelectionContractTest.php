<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FormsController;
use verbb\formie\Formie;
use verbb\formie\services\Permissions;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

it('keeps existing field selections within the current user form access', function (): void {
    $allowed = formie()->form()->settings(['usePerFormPermissions' => true])->singleLineTextField('allowedField')->create();
    $restricted = formie()->form()->settings(['usePerFormPermissions' => true])->singleLineTextField('restrictedField')->create();
    $name = 'fieldSelector' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    // Register permissions after this test's forms have been created.
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_FORMS,
        Permissions::PERM_MANAGE_FORMS . ':' . $allowed->uid,
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
    ]))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($allowed, $restricted, $user): void {
        $request->setIsCpRequest(true);
        $identity = User::find()->id($user->id)->status(null)->one();
        Craft::$app->getUser()->setIdentity($identity);
        Craft::$app->set('sites', new \craft\services\Sites());
        expect($allowed->canView($identity))->toBeTrue()->and($restricted->canView($identity))->toBeFalse();
        $request->setQueryParams(['formId' => 0, 'compact' => true, 'includeFields' => false]);
        $listed = (new FormsController('forms', Formie::$plugin))->runAction('get-existing-fields')->data;
        expect(array_column($listed, 'key'))->toContain($allowed->handle)->not->toContain($restricted->handle);

        $request->setQueryParams(['formId' => 0, 'fieldIds' => [
            $allowed->getFieldByHandle('allowedField')->id,
            $restricted->getFieldByHandle('restrictedField')->id,
        ]]);
        $selected = (new FormsController('forms', Formie::$plugin))->runAction('get-existing-field-configs')->data;
        expect(array_column($selected, 'id'))->toBe([$allowed->getFieldByHandle('allowedField')->id]);
    }, ['method' => 'GET', 'headers' => ['Accept' => 'application/json']]);
});

it('requires the control panel context for existing builder resources', function (string $action): void {
    $form = formie()->form()->create();
    WebRequestTestHelper::withWebRequestContext(function ($request) use ($action, $form): void {
        $request->setIsCpRequest(false);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        $request->setQueryParams(['formId' => $form->id, 'compact' => true, 'includeFields' => false, 'includeNotifications' => false]);
        expect(fn() => (new FormsController('forms', Formie::$plugin))->runAction($action))
            ->toThrow(BadRequestHttpException::class, 'Request must be a control panel request');
    }, ['method' => 'GET', 'headers' => ['Accept' => 'application/json']]);
})->with(['get-existing-fields', 'get-existing-field-configs', 'get-existing-notifications', 'get-form-usage']);

it('requires builder access while preserving stencil-only access', function (bool $canUseStencils): void {
    $name = 'builderRole' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    // Register permissions after this test's forms have been created.
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp', 'accessPlugin-formie', ...($canUseStencils ? ['formie-accessStencils'] : []),
    ]))->toBeTrue();
    WebRequestTestHelper::withWebRequestContext(function ($request) use ($user, $canUseStencils): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        $request->setQueryParams(['formId' => 0]);
        $controller = new FormsController('forms', Formie::$plugin);
        if ($canUseStencils) {
            expect($controller->runAction('get-existing-field-configs')->data)->toBe([]);
        } else {
            expect(fn() => $controller->runAction('get-existing-field-configs'))->toThrow(ForbiddenHttpException::class);
        }
    }, ['method' => 'GET', 'headers' => ['Accept' => 'application/json']]);
})->with([false, true]);
