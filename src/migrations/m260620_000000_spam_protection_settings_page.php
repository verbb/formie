<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;
use verbb\formie\services\Permissions;

use craft\db\Migration;
use craft\db\Query;

class m260620_000000_spam_protection_settings_page extends Migration
{
    public function safeUp(): bool
    {
        $permissionName = (new Permissions())->settingsPagePermissionKey('spam-protection');
        $legacyPermissionNames = [
            (new Permissions())->settingsPagePermissionKey('spam'),
            (new Permissions())->settingsPagePermissionKey('captchas'),
        ];

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

        $permissionId = (int)$permissionId;

        $userIds = (new Query())
            ->select(['upu.userId'])
            ->from(['upu' => Table::USERPERMISSIONS_USERS])
            ->innerJoin(['up' => Table::USERPERMISSIONS], '[[up.id]] = [[upu.permissionId]]')
            ->where(['up.name' => $legacyPermissionNames])
            ->column();

        $userIds = array_unique(array_map('intval', $userIds));

        foreach ($userIds as $userId) {
            $exists = (new Query())
                ->from([Table::USERPERMISSIONS_USERS])
                ->where([
                    'permissionId' => $permissionId,
                    'userId' => $userId,
                ])
                ->exists();

            if ($exists) {
                continue;
            }

            $this->insert(Table::USERPERMISSIONS_USERS, [
                'permissionId' => $permissionId,
                'userId' => $userId,
            ]);
        }

        return true;
    }

    public function safeDown(): bool
    {
        $this->delete(Table::USERPERMISSIONS, [
            'name' => (new Permissions())->settingsPagePermissionKey('spam-protection'),
        ]);

        return true;
    }
}
