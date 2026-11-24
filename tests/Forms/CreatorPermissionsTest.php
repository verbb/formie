<?php

declare(strict_types=1);

use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\models\UserGroup;
use craft\services\UserPermissions;
use verbb\formie\Formie;
use verbb\formie\services\Permissions;

function creatorDirectPermissions(int $userId): array
{
    return (new Query())
        ->select(['p.name'])
        ->from(['p' => Table::USERPERMISSIONS])
        ->innerJoin(['p_u' => Table::USERPERMISSIONS_USERS], '[[p_u.permissionId]] = [[p.id]]')
        ->where(['p_u.userId' => $userId])
        ->column();
}

it('keeps group permissions inherited when granting form creator permissions', function(): void {
    $config = Craft::$app->getProjectConfig();
    $originalGroups = $config->get('users.groups');
    $originalPermissions = Craft::$app->getUserPermissions();
    $transaction = Craft::$app->getDb()->beginTransaction();

    try {
        $suffix = bin2hex(random_bytes(6));
        $group = new UserGroup(['name' => 'Form creators ' . $suffix, 'handle' => 'formCreators' . $suffix]);
        expect(Craft::$app->getUserGroups()->saveGroup($group))->toBeTrue();
        expect(Craft::$app->getUserPermissions()->saveGroupPermissions($group->id, [
            'accessCp',
            'accessPlugin-formie',
            'utility:system-report',
            Permissions::PERM_ACCESS_FORMS,
            Permissions::PERM_CREATE_FORMS,
            'formie-createFormSettings',
            Permissions::PERM_ACCESS_SUBMISSIONS,
            Permissions::PERM_VIEW_SUBMISSIONS,
            ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
        ]))->toBeTrue();

        $user = new User(['username' => 'formCreator' . $suffix, 'email' => 'formCreator' . $suffix . '@example.test']);
        expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
        Craft::$app->getUsers()->assignUserToGroups($user->id, [$group->id]);

        // A direct grant the group also gives, and one it doesn't, both stay direct.
        expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, ['accessCp', 'utility:php-info']))->toBeTrue();

        $form = formie()->form(['title' => 'Creator Form ' . $suffix])->create();

        Craft::$app->set('userPermissions', new UserPermissions());
        $service = Formie::$plugin->getPermissions();
        $identity = User::find()->id($user->id)->status(null)->one();
        expect($service->canManageForm($identity, $form))->toBeFalse();

        $service->grantCreatorPermissions($identity, $form);

        $scope = $service->groupScope(Permissions::GROUP_UNGROUPED);
        $direct = creatorDirectPermissions($user->id);

        // Craft also saves the parents of the scoped grants (such as Access forms) directly, so the grants keep working.

        expect($direct)->toContain(strtolower($service->scopedPermission(Permissions::PERM_MANAGE_FORMS, $scope)))
            ->toContain(strtolower($service->scopedPermission('formie-showFormSettings', $scope)))
            ->toContain(strtolower($service->scopedPermission(Permissions::PERM_VIEW_SUBMISSIONS, $scope)))
            ->toContain(strtolower($service->scopedPermission(Permissions::PERM_DELETE_SUBMISSIONS, $scope)))
            ->toContain('accesscp')
            ->toContain('utility:php-info')
            ->not->toContain('utility:system-report')
            ->not->toContain('accessplugin-formie')
            ->not->toContain(strtolower(Permissions::PERM_CREATE_FORMS))
            ->not->toContain('formie-createformsettings')
            ->not->toContain(strtolower(Permissions::PERM_VIEW_SUBMISSIONS));

        Craft::$app->set('userPermissions', new UserPermissions());
        expect($service->canManageForm(User::find()->id($user->id)->status(null)->one(), $form))->toBeTrue();

        // Removing the user from the group takes its permissions away again.
        Craft::$app->getUsers()->assignUserToGroups($user->id, []);
        Craft::$app->set('userPermissions', new UserPermissions());
        $remaining = Craft::$app->getUserPermissions();

        expect($remaining->doesUserHavePermission($user->id, 'utility:system-report'))->toBeFalse()
            ->and($remaining->doesUserHavePermission($user->id, Permissions::PERM_CREATE_FORMS))->toBeFalse()
            ->and($remaining->doesUserHavePermission($user->id, 'accessCp'))->toBeTrue()
            ->and($remaining->doesUserHavePermission($user->id, 'utility:php-info'))->toBeTrue();
    } finally {
        $config->set('users.groups', $originalGroups);
        $transaction->rollBack();
        Craft::$app->set('userPermissions', $originalPermissions);
    }
});
