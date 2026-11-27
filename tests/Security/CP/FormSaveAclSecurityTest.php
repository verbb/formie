<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FormsController;
use verbb\formie\Formie;
use verbb\formie\models\FormGroup;
use verbb\formie\models\Notification;
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

    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, \Tests\Support\PermissionTestHelper::withParents($permissions)))->toBeTrue();

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

it('preserves notifications when the form manager cannot access them', function(array $notificationParams): void {
    $form = formie()->form(['title' => 'Restricted Notifications Form'])->create();
    $updatedTitle = 'Restricted Notifications Form ' . bin2hex(random_bytes(6));
    $notification = new Notification([
        'name' => 'Original Notification',
        'handle' => 'originalNotification' . bin2hex(random_bytes(6)),
        'enabled' => true,
        'subject' => 'Original subject',
        'to' => 'admin@example.test',
    ]);
    $form->setNotifications([$notification]);

    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $username = 'formNotificationAcl' . bin2hex(random_bytes(6));
    $user = new User(['username' => $username, 'email' => $username . '@example.test']);

    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());

    $permissions = [
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_ACCESS_FORMS,
        Permissions::PERM_VIEW_FORMS,
        Permissions::PERM_MANAGE_FORMS,
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
    ];

    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, \Tests\Support\PermissionTestHelper::withParents($permissions)))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function($request) use ($form, $notificationParams, $updatedTitle, $user): void {
        $request->setIsCpRequest(true);
        $identity = User::find()->id($user->id)->status(null)->one();
        Craft::$app->getUser()->setIdentity($identity);
        $request->setBodyParams(array_merge([
            $request->csrfParam => $request->getCsrfToken(),
            'id' => $form->id,
            'siteId' => Craft::$app->getSites()->getCurrentSite()->id,
            'title' => $updatedTitle,
        ], $notificationParams));

        expect(Formie::$plugin->getPermissions()->canManageForm($identity, $form))->toBeTrue()
            ->and(Formie::$plugin->getPermissions()->canShowFormBuilderTab($identity, $form, 'formie-showNotifications'))->toBeFalse();

        (new FormsController('forms', Formie::$plugin))->runAction('save');
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);

    $notifications = Formie::$plugin->getNotifications()->getFormNotifications($form);

    expect($notifications)->toHaveCount(1)
        ->and($notifications[0]->handle)->toBe($notification->handle)
        ->and($notifications[0]->subject)->toBe('Original subject')
        ->and(Formie::$plugin->getForms()->getFormById((int)$form->id)?->title)->toBe($updatedTitle);
})->with([
    'forged replacement' => [[
        'notifications' => [[
            'name' => 'Forged Notification',
            'handle' => 'forgedNotification',
            'subject' => 'Forged subject',
            'to' => 'attacker@example.test',
        ]],
    ]],
    'omitted notifications' => [[]],
])->group('security');

it('allows trusted stencil hydration to include posted notifications', function(): void {
    $form = formie()->form(['title' => 'Stencil Notification Source'])->create();
    $username = 'stencilNotificationAcl' . bin2hex(random_bytes(6));
    $user = new User(['username' => $username, 'email' => $username . '@example.test']);

    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());

    $permissions = [
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_ACCESS_FORMS,
        Permissions::PERM_VIEW_FORMS,
        Permissions::PERM_MANAGE_FORMS,
        'formie-accessStencils',
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
    ];

    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, \Tests\Support\PermissionTestHelper::withParents($permissions)))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function($request) use ($form, $user): void {
        $request->setIsCpRequest(true);
        $identity = User::find()->id($user->id)->status(null)->one();
        Craft::$app->getUser()->setIdentity($identity);
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'id' => $form->id,
            'siteId' => Craft::$app->getSites()->getCurrentSite()->id,
            'notifications' => [[
                'name' => 'Stencil Notification',
                'handle' => 'stencilNotification',
                'subject' => 'Stencil subject',
                'to' => 'admin@example.test',
            ]],
        ]);

        expect(Formie::$plugin->getPermissions()->canShowFormBuilderTab($identity, $form, 'formie-showNotifications'))->toBeFalse();

        $stencilForm = Formie::$plugin->getForms()->buildFormFromPost(allowNotificationChanges: true);

        expect($stencilForm->getNotifications())->toHaveCount(1)
            ->and($stencilForm->getNotifications()[0]->handle)->toBe('stencilNotification');
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);
})->group('security');

it('refuses moving a form into a group the user cannot manage with only the form\'s own manage grant', function(): void {
    $groups = [];

    foreach (['a', 'b', 'c'] as $key) {
        $groups[$key] = new FormGroup([
            'name' => 'Dedicated Move ' . strtoupper($key),
            'handle' => 'dedicatedMove' . strtoupper($key) . bin2hex(random_bytes(6)),
        ]);

        expect(Formie::$plugin->getFormGroups()->saveGroup($groups[$key]))->toBeTrue();
    }

    $form = formie()->form(['title' => 'Dedicated Move Form', 'groupId' => $groups['a']->id])
        ->settings(['usePerFormPermissions' => true])
        ->create();
    $username = 'dedicatedMove' . bin2hex(random_bytes(6));
    $user = new User(['username' => $username, 'email' => $username . '@example.test']);

    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());

    $permissionService = Formie::$plugin->getPermissions();

    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, \Tests\Support\PermissionTestHelper::withParents([
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_ACCESS_FORMS,
        $permissionService->scopedPermission(Permissions::PERM_MANAGE_FORMS, $permissionService->formScope($form)),
        $permissionService->scopedPermission(Permissions::PERM_MANAGE_FORMS, $permissionService->groupScope($groups['c']->handle)),
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
    ])))->toBeTrue();

    $save = fn(?int $groupId, string $title) => WebRequestTestHelper::withWebRequestContext(function($request) use ($form, $user, $groupId, $title): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'id' => $form->id,
            'siteId' => Craft::$app->getSites()->getCurrentSite()->id,
            'title' => $title,
            'groupId' => $groupId,
        ]);

        (new FormsController('forms', Formie::$plugin))->runAction('save');
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);
    $stored = fn() => Formie::$plugin->getForms()->getFormById((int)$form->id);

    expect(fn() => $save((int)$groups['b']->id, 'Dedicated Move Form'))
        ->toThrow(ForbiddenHttpException::class, 'User is not permitted to perform this action')
        ->and((int)$stored()->groupId)->toBe((int)$groups['a']->id);

    // The form's own grant still covers saving it in its group, and the user can move it into a group they manage.
    $save((int)$groups['a']->id, 'Dedicated Move Form Renamed');

    expect($stored()->title)->toBe('Dedicated Move Form Renamed');

    $save((int)$groups['c']->id, 'Dedicated Move Form Renamed');

    expect((int)$stored()->groupId)->toBe((int)$groups['c']->id);
})->group('security');
