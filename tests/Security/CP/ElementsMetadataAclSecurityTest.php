<?php

declare(strict_types=1);

use craft\elements\User;
use craft\services\UserPermissions;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\ElementsController;
use yii\base\Action;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

function elementsMetadataAclUser(string $permission): User
{
    $username = 'elementsMetadataAcl' . bin2hex(random_bytes(6));
    $user = new User(['username' => $username, 'email' => $username . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp',
        'accessPlugin-formie',
        $permission,
    ]))->toBeTrue();

    return $user;
}

it('rejects element metadata requests outside the control panel', function (): void {
    Craft::$app->set('userPermissions', new UserPermissions());
    $user = elementsMetadataAclUser('formie-accessForms');

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($user): void {
        $request->setIsCpRequest(false);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        $controller = new ElementsController('formie-elements-security', Craft::$app);

        expect(fn() => $controller->beforeAction(new Action('sections', $controller)))
            ->toThrow(BadRequestHttpException::class, 'Request must be a control panel request');
    }, ['headers' => ['Accept' => 'application/json']]);
})->group('security');

it('rejects control panel users without forms or stencils access', function (): void {
    Craft::$app->set('userPermissions', new UserPermissions());
    $user = elementsMetadataAclUser('formie-accessSubmissions');

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($user): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        $controller = new ElementsController('formie-elements-security', Craft::$app);

        expect(fn() => $controller->beforeAction(new Action('sections', $controller)))
            ->toThrow(ForbiddenHttpException::class);
    }, ['headers' => ['Accept' => 'application/json']]);
})->group('security');

it('allows forms and stencil builders to use every element metadata action', function (string $permission): void {
    Craft::$app->set('userPermissions', new UserPermissions());
    $user = elementsMetadataAclUser($permission);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($user): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        $controller = new ElementsController('formie-elements-security', Craft::$app);

        foreach (['sections', 'entry-types', 'category-groups', 'tag-groups', 'product-types'] as $actionId) {
            expect($controller->beforeAction(new Action($actionId, $controller)))->toBeTrue();
        }

        expect($controller->actionSections()->data)->toMatchArray(['success' => true]);
    }, ['headers' => ['Accept' => 'application/json']]);
})->with(['formie-accessForms', 'formie-accessStencils'])->group('security');
