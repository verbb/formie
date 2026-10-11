<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m260617_000001_report_permissions;
use verbb\formie\services\Permissions;

it('preserves report access without granting report management during upgrades', function (): void {
    $transaction = Craft::$app->getDb()->beginTransaction();

    try {
        $user = new User([
            'username' => 'reportMigration' . uniqid(),
            'email' => uniqid() . '@example.test',
        ]);
        expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

        $sourcePermission = strtolower(Permissions::PERM_ACCESS_SUBMISSIONS);
        Craft::$app->getDb()->createCommand()->upsert(
            Table::USERPERMISSIONS,
            ['name' => $sourcePermission],
            false,
        )->execute();
        $sourcePermissionId = (new Query())
            ->select(['id'])
            ->from([Table::USERPERMISSIONS])
            ->where(['name' => $sourcePermission])
            ->scalar();
        Craft::$app->getDb()->createCommand()->insert(Table::USERPERMISSIONS_USERS, [
            'permissionId' => $sourcePermissionId,
            'userId' => $user->id,
        ])->execute();

        expect((new m260617_000001_report_permissions())->safeUp())->toBeTrue();

        $grants = (new Query())
            ->select(['up.name'])
            ->from(['upu' => Table::USERPERMISSIONS_USERS])
            ->innerJoin(['up' => Table::USERPERMISSIONS], '[[up.id]] = [[upu.permissionId]]')
            ->where(['upu.userId' => $user->id])
            ->column();

        expect($grants)
            ->toContain(strtolower(Permissions::PERM_ACCESS_REPORTS))
            ->toContain(strtolower(Permissions::PERM_EXPORT_SUBMISSIONS))
            ->not->toContain(strtolower(Permissions::PERM_MANAGE_REPORTS))
            ->not->toContain(strtolower(Permissions::PERM_MANAGE_SCHEDULED_REPORTS));
    } finally {
        $transaction->rollBack();
    }
})->group('security');
