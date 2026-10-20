<?php

use craft\db\Query;
use craft\elements\User;
use craft\models\UserGroup;
use craft\services\UserPermissions;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261020_010000_permission_cleanup;

it('migrates historical permissions directly to final capabilities for direct and inherited users', function(array $legacy, array $targets): void {
    $config = Craft::$app->getProjectConfig();
    $originalGroups = $config->get('users.groups');
    $originalReadOnly = $config->readOnly;
    $permissions = Craft::$app->getUserPermissions();
    $transaction = Craft::$app->getDb()->beginTransaction();

    try {
        $suffix = bin2hex(random_bytes(6));
        $group = new UserGroup(['name' => 'Production settings ' . $suffix, 'handle' => 'productionSettings' . $suffix]);
        expect(Craft::$app->getUserGroups()->saveGroup($group))->toBeTrue();
        $direct = new User(['username' => 'prodDirect' . $suffix, 'email' => 'prodDirect' . $suffix . '@example.test']);
        $inherited = new User(['username' => 'prodInherited' . $suffix, 'email' => 'prodInherited' . $suffix . '@example.test']);
        expect(Craft::$app->getElements()->saveElement($direct))->toBeTrue();
        expect(Craft::$app->getElements()->saveElement($inherited))->toBeTrue();
        Craft::$app->getUsers()->assignUserToGroups($inherited->id, [$group->id]);
        $original = ['accesscp', 'accessplugin-formie', 'formie-accessforms', ...$legacy];
        $path = 'users.groups.' . $group->uid . '.permissions';
        $config->set($path, $original);

        foreach ($original as $name) {
            Craft::$app->getDb()->createCommand()->upsert(Table::USERPERMISSIONS, ['name' => $name], false)->execute();
            $id = (new Query())->select('id')->from(Table::USERPERMISSIONS)->where(['name' => $name])->scalar();
            Craft::$app->getDb()->createCommand()->upsert(Table::USERPERMISSIONS_USERS, ['permissionId' => $id, 'userId' => $direct->id], false)->execute();
        }

        $config->readOnly = true;
        $migration = new m261020_010000_permission_cleanup();
        expect($migration->safeUp())->toBeTrue()->and($migration->safeUp())->toBeTrue();
        expect($config->readOnly)->toBeTrue();
        $expected = array_values(array_unique(['accesscp', 'accessplugin-formie', 'formie-accessforms', ...$targets]));
        sort($expected);
        $configured = $config->get($path);
        sort($configured);
        expect($configured)->toBe($expected);

        $retired = array_values(array_diff($legacy, $targets));
        expect((new Query())->from(Table::USERPERMISSIONS)->where(['name' => $retired])->exists())->toBeFalse();

        // Verify both assignment tables before project config can restore group grants.
        foreach ([Table::USERPERMISSIONS_USERS => ['userId', $direct->id], Table::USERPERMISSIONS_USERGROUPS => ['groupId', $group->id]] as $table => [$column, $ownerId]) {
            $stored = (new Query())->select('up.name')->from(['assignment' => $table])
                ->innerJoin(['up' => Table::USERPERMISSIONS], '[[up.id]] = [[assignment.permissionId]]')
                ->where(['assignment.' . $column => $ownerId])->column();
            sort($stored);
            expect($stored)->toBe($expected);
        }

        // Reapplying the migrated config and saving both editor forms must keep all grants.
        $config->readOnly = false;
        $config->set($path, $expected, force: true);
        Craft::$app->set('userPermissions', new UserPermissions());
        foreach ([$direct, $inherited] as $user) {
            $actual = Craft::$app->getUserPermissions()->getPermissionsByUserId($user->id);
            sort($actual);
            expect($actual)->toBe($expected);
            expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $user === $direct ? $actual : []))->toBeTrue();
            $fresh = User::find()->id($user->id)->status(null)->one();
            foreach ($expected as $grant) {
                expect($fresh->can($grant))->toBeTrue();
            }
            $access = Formie::$plugin->getPermissions();
            expect($access->canAccessSettingsPage($fresh, 'defaults'))->toBeFalse();
            expect($access->canManageScheduledReports($fresh))->toBeFalse();
            expect($access->canTestScheduledReports($fresh))->toBe(in_array('formie-testscheduledreports', $expected, true));
            expect($access->canManageSpamRules($fresh))->toBe(in_array('formie-managespamrules', $expected, true));
            expect($access->canManageCaptchas($fresh))->toBe(in_array('formie-managecaptchas', $expected, true));
            expect($access->canImportForms($fresh))->toBe(in_array('formie-importforms', $expected, true));
            expect($access->canExportForms($fresh))->toBe(in_array('formie-exportforms', $expected, true));
        }
        expect(Craft::$app->getUserPermissions()->saveGroupPermissions($group->id, $expected))->toBeTrue();
        expect($config->get($path))->toBe($expected);
    } finally {
        $config->readOnly = false;
        $config->set('users.groups', $originalGroups);
        $config->readOnly = $originalReadOnly;
        $transaction->rollBack();
        Craft::$app->set('userPermissions', $permissions);
    }
})->with([
    'blanket settings' => [['formie-accesssettings'], [
        'formie-accesssettingssection', 'formie-settingsencryption', 'formie-settingsimportexport',
        'formie-settingssyncedfields', 'formie-settingsscheduledreports', 'formie-settingssupport',
        'formie-settingsmigratefreeform4', 'formie-settingsmigratefreeform5', 'formie-settingsmigratesproutforms',
        'formie-accessspamprotection', 'formie-managespamrules', 'formie-managecaptchas',
    ]],
    'schedule manager' => [['formie-managescheduledreports'], ['formie-accesssettingssection', 'formie-settingsscheduledreports', 'formie-testscheduledreports']],
    'retired configuration' => [[
        'formie-settingsgeneral', 'formie-settingsforms', 'formie-settingsformgroups', 'formie-settingsdefaults',
        'formie-settingsfields', 'formie-settingsnotifications', 'formie-settingssentnotifications',
        'formie-settingsstatuses', 'formie-settingssubmissions', 'formie-settingsintegrationssettings',
        'formie-settingsformtemplates', 'formie-settingsemailtemplates', 'formie-settingspdftemplates',
    ], []],
    'legacy captchas' => [['formie-settingscaptchas'], ['formie-accessspamprotection', 'formie-managecaptchas', 'formie-managespamrules']],
    'independent import' => [['formie-importforms'], ['formie-accesssettingssection', 'formie-settingsimportexport', 'formie-importforms']],
    'independent export' => [['formie-exportforms'], ['formie-accesssettingssection', 'formie-settingsimportexport', 'formie-exportforms']],
    'read-only usage' => [['formie-settingssyncedfields'], ['formie-accesssettingssection', 'formie-settingssyncedfields']],
    'read-only schedules' => [['formie-settingsscheduledreports'], ['formie-accesssettingssection', 'formie-settingsscheduledreports']],
    'legacy spam' => [['formie-settingsspam'], ['formie-accessspamprotection', 'formie-managecaptchas', 'formie-managespamrules']],
    'legacy spamprotection' => [['formie-settingsspamprotection'], ['formie-accessspamprotection', 'formie-managecaptchas', 'formie-managespamrules']],
    'retired general' => [['formie-settingsgeneral'], []],
    'retired forms' => [['formie-settingsforms'], []],
    'retired formgroups' => [['formie-settingsformgroups'], []],
    'retired defaults' => [['formie-settingsdefaults'], []],
    'retired fields' => [['formie-settingsfields'], []],
    'retired notifications' => [['formie-settingsnotifications'], []],
    'retired sentnotifications' => [['formie-settingssentnotifications'], []],
    'retired statuses' => [['formie-settingsstatuses'], []],
    'retired submissionstatuses' => [['formie-settingssubmissionstatuses'], []],
    'retired formstatuses' => [['formie-settingsformstatuses'], []],
    'retired submissions' => [['formie-settingssubmissions'], []],
    'retired integrationssettings' => [['formie-settingsintegrationssettings'], []],
    'retired formtemplates' => [['formie-settingsformtemplates'], []],
    'retired emailtemplates' => [['formie-settingsemailtemplates'], []],
    'retired pdftemplates' => [['formie-settingspdftemplates'], []],
    'retained encryption' => [['formie-settingsencryption'], ['formie-accesssettingssection', 'formie-settingsencryption']],
    'retained importexport' => [['formie-settingsimportexport'], ['formie-accesssettingssection', 'formie-settingsimportexport']],
    'retained support' => [['formie-settingssupport'], ['formie-accesssettingssection', 'formie-settingssupport']],
    'retained migratefreeform4' => [['formie-settingsmigratefreeform4'], ['formie-accesssettingssection', 'formie-settingsmigratefreeform4']],
    'retained migratefreeform5' => [['formie-settingsmigratefreeform5'], ['formie-accesssettingssection', 'formie-settingsmigratefreeform5']],
    'retained migratesproutforms' => [['formie-settingsmigratesproutforms'], ['formie-accesssettingssection', 'formie-settingsmigratesproutforms']],
    'retained accessstencils' => [['formie-accessstencils'], ['formie-accessstencilssection', 'formie-accessstencils']],
    'retained accessintegrations' => [['formie-accessintegrations'], ['formie-accessintegrationssection', 'formie-accessintegrations']],
    'retained managecaptchas' => [['formie-managecaptchas'], ['formie-accessspamprotection', 'formie-managecaptchas']],
    'retained managespamrules' => [['formie-managespamrules'], ['formie-accessspamprotection', 'formie-managespamrules']],
    'already nested import' => [['formie-accesssettingssection', 'formie-settingsimportexport', 'formie-importforms'], ['formie-accesssettingssection', 'formie-settingsimportexport', 'formie-importforms']],
    'overlapping spam grants' => [['formie-settingsspam', 'formie-settingscaptchas', 'formie-managespamrules'], ['formie-accessspamprotection', 'formie-managecaptchas', 'formie-managespamrules']],
    'unrelated grants' => [['formie-accessreports'], ['formie-accessreports']],
]);

it('allows a direct settings grant to use its inherited parent without granting other settings', function(): void {
    $config = Craft::$app->getProjectConfig();
    $originalGroups = $config->get('users.groups');
    $transaction = Craft::$app->getDb()->beginTransaction();
    try {
        $suffix = bin2hex(random_bytes(6));
        $group = new UserGroup(['name' => 'Settings parent ' . $suffix, 'handle' => 'settingsParent' . $suffix]);
        expect(Craft::$app->getUserGroups()->saveGroup($group))->toBeTrue();
        expect(Craft::$app->getUserPermissions()->saveGroupPermissions($group->id, ['formie-accessSettingsSection']))->toBeTrue();
        $user = new User(['username' => 'settingsChild' . $suffix, 'email' => 'settingsChild' . $suffix . '@example.test']);
        expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
        Craft::$app->getUsers()->assignUserToGroups($user->id, [$group->id]);
        $access = Formie::$plugin->getPermissions();
        expect($access->canAccessAnySettings($user))->toBeFalse();
        expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, ['formie-settingsSyncedFields']))->toBeTrue();
        $fresh = User::find()->id($user->id)->status(null)->one();
        expect($access->canAccessSettingsPage($fresh, 'synced-fields'))->toBeTrue();
        expect($access->canAccessSettingsPage($fresh, 'fields'))->toBeFalse();
        expect($access->canManageCaptchas($fresh))->toBeFalse();
        expect($access->canManageSpamRules($fresh))->toBeFalse();
    } finally {
        $config->set('users.groups', $originalGroups);
        $transaction->rollBack();
    }
});
