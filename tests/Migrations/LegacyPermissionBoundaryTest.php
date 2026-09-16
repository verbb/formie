<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;

it('preserves restricted and global submission grants for users and groups upgrading from Formie 2', function (): void {
    $allowed = formie()->form()->singleLineTextField('message')->create();
    $denied = formie()->form()->singleLineTextField('message')->create();
    $restricted = new User(['username' => 'legacyRestricted' . uniqid(), 'email' => uniqid() . '@example.test']);
    $global = new User(['username' => 'legacyGlobal' . uniqid(), 'email' => uniqid() . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($restricted))->toBeTrue();
    expect(Craft::$app->getElements()->saveElement($global))->toBeTrue();
    $permissions = Craft::$app->getUserPermissions();
    $originalGroups = Craft::$app->getProjectConfig()->get('users.groups');
    $transaction = Craft::$app->getDb()->beginTransaction();
    try {
        $group = new \craft\models\UserGroup(['name' => 'Legacy ' . uniqid(), 'handle' => 'legacy' . uniqid()]);
        expect(Craft::$app->getUserGroups()->saveGroup($group))->toBeTrue();
        // Current Craft filters obsolete permission names on save; seed the exact
        // persisted rows that the historical installation supplies to migrations.
        $seedGrants = static function(string $table, string $column, int $id, array $names): void {
            foreach ($names as $name) {
                $name = strtolower($name);
                Craft::$app->getDb()->createCommand()->upsert(Table::USERPERMISSIONS, ['name' => $name], false)->execute();
                $permissionId = (new Query())->select('id')->from(Table::USERPERMISSIONS)->where(['name' => $name])->scalar();
                Craft::$app->getDb()->createCommand()->upsert($table, ['permissionId' => $permissionId, $column => $id], false)->execute();
            }
        };
        $seedGrants(Table::USERPERMISSIONS_USERS, 'userId', $restricted->id, ['accessCp', 'accessPlugin-formie', 'formie-viewSubmissions', 'formie-manageSubmission:' . $allowed->uid]);
        $seedGrants(Table::USERPERMISSIONS_USERS, 'userId', $global->id, ['formie-editSubmissions']);
        $seedGrants(Table::USERPERMISSIONS_USERGROUPS, 'groupId', $group->id, ['formie-viewSubmissions', 'formie-manageSubmission:' . $allowed->uid]);
        $configPath = 'users.groups.' . $group->uid . '.permissions';
        Craft::$app->getProjectConfig()->set($configPath, array_map('strtolower', ['formie-viewSubmissions', 'formie-manageSubmission:' . $allowed->uid]));
        (new \verbb\formie\migrations\m240130_000000_permissions())->safeUp();
        (new \verbb\formie\migrations\m260615_000000_group_permissions())->safeUp();
        // Read fresh persisted grants; earlier permission-service caches contain legacy names.
        Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
        $service = Formie::$plugin->getPermissions();
        expect($service->canViewSubmissions($restricted, $allowed))->toBeTrue();
        expect($service->canViewSubmissions($restricted, $denied))->toBeFalse();
        expect($service->canViewSubmissions($global, $denied))->toBeTrue();
        $groupGrants = Craft::$app->getUserPermissions()->getPermissionsByGroupId($group->id);
        expect($groupGrants)->toContain(strtolower('formie-viewSubmissions:' . $allowed->uid));
        expect($groupGrants)->not->toContain(strtolower('formie-viewSubmissions'));
        expect($groupGrants)->not->toContain(strtolower('formie-viewSubmissions:group:ungrouped'));
        expect(Craft::$app->getProjectConfig()->get($configPath))->toBe($groupGrants);
    } finally {
        Craft::$app->getProjectConfig()->set('users.groups', $originalGroups);
        $transaction->rollBack();
        Craft::$app->set('userPermissions', $permissions);
    }
});
