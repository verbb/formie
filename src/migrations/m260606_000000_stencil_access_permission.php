<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;

class m260606_000000_stencil_access_permission extends Migration
{
    public function safeUp(): bool
    {
        // Craft stores permission names lowercased and matches them case-sensitively when
        // reading them back, so any rows we create here must already be lowercase.
        $permissionName = strtolower('formie-accessStencils');

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

        $userIds = (new Query())
            ->select(['upu.userId'])
            ->from(['upu' => Table::USERPERMISSIONS_USERS])
            ->innerJoin(['up' => Table::USERPERMISSIONS], '[[up.id]] = [[upu.permissionId]]')
            ->where(['up.name' => strtolower('formie-accessForms')])
            ->column();

        $userIds = array_unique(array_map('intval', $userIds));

        if ($userIds === []) {
            return true;
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

        return true;
    }

    public function safeDown(): bool
    {
        $this->delete(Table::USERPERMISSIONS, [
            'name' => strtolower('formie-accessStencils'),
        ]);

        return true;
    }
}
