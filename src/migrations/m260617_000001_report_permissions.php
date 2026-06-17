<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;
use verbb\formie\services\Permissions;

use craft\db\Migration;
use craft\db\Query;

class m260617_000001_report_permissions extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $permissions = [
            Permissions::PERM_ACCESS_REPORTS,
            Permissions::PERM_MANAGE_REPORTS,
            Permissions::PERM_EXPORT_SUBMISSIONS,
            Permissions::PERM_MANAGE_SCHEDULED_REPORTS,
        ];

        $sourcePermission = Permissions::PERM_ACCESS_SUBMISSIONS;
        $userIds = (new Query())
            ->select(['upu.userId'])
            ->from(['upu' => Table::USERPERMISSIONS_USERS])
            ->innerJoin(['up' => Table::USERPERMISSIONS], '[[up.id]] = [[upu.permissionId]]')
            ->where(['up.name' => $sourcePermission])
            ->column();

        $userIds = array_unique(array_map('intval', $userIds));

        foreach ($permissions as $permissionName) {
            $permissionId = (new Query())
                ->select(['id'])
                ->from([Table::USERPERMISSIONS])
                ->where(['name' => $permissionName])
                ->scalar();

            if (!$permissionId) {
                $this->insert(Table::USERPERMISSIONS, [
                    'name' => $permissionName,
                ]);

                $permissionId = $this->db->getLastInsertID(Table::USERPERMISSIONS);
            }

            if ($userIds === []) {
                continue;
            }

            $existingUserIds = (new Query())
                ->select(['upu.userId'])
                ->from(['upu' => Table::USERPERMISSIONS_USERS])
                ->where(['upu.permissionId' => $permissionId])
                ->column();

            $insert = [];

            foreach (array_diff($userIds, $existingUserIds) as $userId) {
                $insert[] = [$permissionId, $userId];
            }

            if ($insert !== []) {
                $this->batchInsert(Table::USERPERMISSIONS_USERS, ['permissionId', 'userId'], $insert);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        $this->delete(Table::USERPERMISSIONS, [
            'name' => [
                Permissions::PERM_ACCESS_REPORTS,
                Permissions::PERM_MANAGE_REPORTS,
                Permissions::PERM_EXPORT_SUBMISSIONS,
                Permissions::PERM_MANAGE_SCHEDULED_REPORTS,
            ],
        ]);

        return true;
    }
}
