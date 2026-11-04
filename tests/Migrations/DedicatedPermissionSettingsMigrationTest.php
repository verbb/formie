<?php

use craft\controllers\UsersController;
use craft\db\Query;
use craft\elements\User;
use craft\helpers\Json;
use craft\models\UserGroup;
use craft\services\UserPermissions;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261104_010000_dedicated_permission_settings;

function dedicatedPermissionGrant(string $table, string $column, int $id, string $name): void
{
    $db = Craft::$app->getDb();
    $name = strtolower($name);
    $db->createCommand()->upsert(Table::USERPERMISSIONS, ['name' => $name], false)->execute();
    $permissionId = (new Query())->select('id')->from(Table::USERPERMISSIONS)->where(['name' => $name])->scalar();
    $db->createCommand()->upsert($table, ['permissionId' => $permissionId, $column => $id], false)->execute();
}

function dedicatedPermissionSettings(Form $form): array
{
    return Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar());
}

function dedicatedPermissionUser(): User
{
    $user = new User(['username' => 'perform' . uniqid(), 'email' => uniqid() . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    return $user;
}

function dedicatedPermissionReload(int $id): User
{
    Craft::$app->set('userPermissions', new UserPermissions());

    return User::find()->id($id)->status(null)->one();
}

it('keeps a per-form submission grant when an unchanged user permissions save runs after upgrading', function (): void {
    $allowed = formie()->form()->singleLineTextField('message')->create();
    $restricted = formie()->form()->singleLineTextField('message')->create();
    $user = dedicatedPermissionUser();
    $admin = User::find()->admin(true)->one();
    $permissions = Craft::$app->getUserPermissions();
    $service = Formie::$plugin->getPermissions();
    $transaction = Craft::$app->getDb()->beginTransaction();

    try {
        foreach (['accessCp', 'accessPlugin-formie', 'formie-accessSubmissions', 'formie-viewSubmissions:' . $allowed->uid] as $name) {
            dedicatedPermissionGrant(Table::USERPERMISSIONS_USERS, 'userId', $user->id, $name);
        }

        (new m261104_010000_dedicated_permission_settings())->safeUp();

        expect(dedicatedPermissionSettings($allowed)['usePerFormPermissions'])->toBeTrue()
            ->and(dedicatedPermissionSettings($restricted)['usePerFormPermissions'] ?? false)->toBeFalse();

        Formie::$plugin->getForms()->invalidateFormCaches();
        $before = dedicatedPermissionReload($user->id);
        $grants = Craft::$app->getUserPermissions()->getPermissionsByUserId($user->id);
        expect($service->canViewSubmissions($before, $allowed))->toBeTrue()
            ->and($service->canViewSubmissions($before, $restricted))->toBeFalse();

        $response = WebRequestTestHelper::withWebRequestContext(function($request) use ($admin) {
            $request->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity($admin);

            return (new UsersController('users', Craft::$app))->actionSavePermissions();
        }, ['method' => 'POST', 'requestUri' => '/admin/actions/users/save-permissions', 'bodyParams' => ['userId' => $user->id, 'permissions' => $grants], 'headers' => ['Accept' => 'application/json']]);

        expect($response->statusCode)->toBe(200);

        $after = dedicatedPermissionReload($user->id);
        expect(Craft::$app->getUserPermissions()->getPermissionsByUserId($user->id))->toContain(strtolower('formie-viewSubmissions:' . $allowed->uid))
            ->and($service->canViewSubmissions($after, $allowed))->toBeTrue()
            ->and($service->canViewSubmissions($after, $restricted))->toBeFalse();
    } finally {
        $transaction->rollBack();
        Craft::$app->set('userPermissions', $permissions);
        Formie::$plugin->getForms()->invalidateFormCaches();
    }
});

it('registers every historical per-form permission once the form uses dedicated permissions', function (): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $user = dedicatedPermissionUser();
    $permissions = Craft::$app->getUserPermissions();
    $transaction = Craft::$app->getDb()->beginTransaction();

    try {
        dedicatedPermissionGrant(Table::USERPERMISSIONS_USERS, 'userId', $user->id, 'formie-manageForms:' . $form->uid);
        (new m261104_010000_dedicated_permission_settings())->safeUp();
        Formie::$plugin->getForms()->invalidateFormCaches();
        Craft::$app->set('userPermissions', new UserPermissions());

        $registered = [];

        foreach (Craft::$app->getUserPermissions()->getAllPermissions() as $group) {
            $walk = function(array $items) use (&$walk, &$registered): void {
                foreach ($items as $name => $data) {
                    $registered[] = strtolower($name);
                    $walk($data['nested'] ?? []);
                }
            };
            $walk($group['permissions']);
        }

        foreach (['formie-manageForms', 'formie-viewSubmissions', 'formie-createSubmissions', 'formie-saveSubmissions', 'formie-deleteSubmissions', 'formie-viewSentNotifications', 'formie-resendSentNotifications', 'formie-deleteSentNotifications'] as $base) {
            expect($registered)->toContain(strtolower($base . ':' . $form->uid));
        }
    } finally {
        $transaction->rollBack();
        Craft::$app->set('userPermissions', $permissions);
        Formie::$plugin->getForms()->invalidateFormCaches();
    }
});

it('turns on dedicated permissions for group grants, including those only in project config, and leaves other settings alone', function (): void {
    $direct = formie()->form()->singleLineTextField('message')->create();
    $configured = formie()->form()->singleLineTextField('message')->create();
    $grouped = formie()->form()->singleLineTextField('message')->create();
    $untouched = formie()->form()->singleLineTextField('message')->create();
    $projectConfig = Craft::$app->getProjectConfig();
    $originalGroups = $projectConfig->get('users.groups');
    $permissions = Craft::$app->getUserPermissions();
    $transaction = Craft::$app->getDb()->beginTransaction();

    try {
        $group = new UserGroup(['name' => 'Per form ' . uniqid(), 'handle' => 'performGroup' . uniqid()]);
        expect(Craft::$app->getUserGroups()->saveGroup($group))->toBeTrue();
        // Applying project config rewrites the group's rows, so seed the row-only grant afterwards.
        $projectConfig->set('users.groups.' . $group->uid . '.permissions', [strtolower('formie-viewSubmissions:' . $configured->uid), strtolower('formie-viewSubmissions:group:ungrouped'), 'accesscp']);
        dedicatedPermissionGrant(Table::USERPERMISSIONS_USERGROUPS, 'groupId', $group->id, 'formie-viewSubmissions:' . $direct->uid);

        // An unassigned permission row is not a grant.
        Craft::$app->getDb()->createCommand()->upsert(Table::USERPERMISSIONS, ['name' => strtolower('formie-viewSubmissions:' . $grouped->uid)], false)->execute();

        $settings = dedicatedPermissionSettings($direct);
        (new m261104_010000_dedicated_permission_settings())->safeUp();
        $migrated = dedicatedPermissionSettings($direct);

        expect($migrated['usePerFormPermissions'])->toBeTrue()
            ->and(array_diff_key($migrated, ['usePerFormPermissions' => true]))->toBe(array_diff_key($settings, ['usePerFormPermissions' => true]))
            ->and(dedicatedPermissionSettings($configured)['usePerFormPermissions'])->toBeTrue()
            ->and(dedicatedPermissionSettings($grouped)['usePerFormPermissions'] ?? false)->toBeFalse()
            ->and(dedicatedPermissionSettings($untouched)['usePerFormPermissions'] ?? false)->toBeFalse();

        // Running again changes nothing.
        (new m261104_010000_dedicated_permission_settings())->safeUp();
        expect(dedicatedPermissionSettings($direct))->toBe($migrated);
    } finally {
        $projectConfig->set('users.groups', $originalGroups);
        $transaction->rollBack();
        Craft::$app->set('userPermissions', $permissions);
        Formie::$plugin->getForms()->invalidateFormCaches();
    }
});
