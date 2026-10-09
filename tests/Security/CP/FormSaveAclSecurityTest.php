<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FormsController;
use verbb\formie\Formie;
use verbb\formie\models\FormGroup;
use verbb\formie\services\Permissions;
use yii\web\ForbiddenHttpException;

it('authorizes form saves against the persisted group', function(): void {
    $allowedGroup = new FormGroup([
        'name' => 'Allowed Save Group',
        'handle' => 'allowedSaveGroup' . bin2hex(random_bytes(6)),
    ]);
    $restrictedGroup = new FormGroup([
        'name' => 'Restricted Save Group',
        'handle' => 'restrictedSaveGroup' . bin2hex(random_bytes(6)),
    ]);

    expect(Formie::$plugin->getFormGroups()->saveGroup($allowedGroup))->toBeTrue()
        ->and(Formie::$plugin->getFormGroups()->saveGroup($restrictedGroup))->toBeTrue();

    $restrictedForm = formie()->form([
        'title' => 'Restricted Group Form',
        'groupId' => $restrictedGroup->id,
    ])->create();
    $username = 'formSaveAcl' . bin2hex(random_bytes(6));
    $user = new User(['username' => $username, 'email' => $username . '@example.test']);

    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());

    $permissions = [
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_ACCESS_FORMS,
        Permissions::PERM_VIEW_FORMS,
        Formie::$plugin->getPermissions()->scopedPermission(
            Permissions::PERM_MANAGE_FORMS,
            Formie::$plugin->getPermissions()->groupScope($allowedGroup->handle),
        ),
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
    ];

    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $permissions))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function($request) use ($allowedGroup, $restrictedForm, $user): void {
        $request->setIsCpRequest(true);
        $identity = User::find()->id($user->id)->status(null)->one();
        Craft::$app->getUser()->setIdentity($identity);
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'id' => $restrictedForm->id,
            'siteId' => Craft::$app->getSites()->getCurrentSite()->id,
            'groupId' => $allowedGroup->id,
        ]);

        expect(Formie::$plugin->getPermissions()->canManageForm($identity, $restrictedForm))->toBeFalse();

        expect(fn() => (new FormsController('forms', Formie::$plugin))->runAction('save'))
            ->toThrow(ForbiddenHttpException::class, 'User is not permitted to perform this action');
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);

    $reloaded = Formie::$plugin->getForms()->getFormById((int)$restrictedForm->id);

    expect($reloaded)->not->toBeNull()
        ->and((int)$reloaded->groupId)->toBe((int)$restrictedGroup->id);
})->group('security');
