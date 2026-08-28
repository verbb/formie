<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\services\Permissions;

use Craft;
use craft\db\Migration;
use craft\db\Query;

class m260617_000000_settings_page_permissions extends Migration
{
    public function safeUp(): bool
    {
        $permissions = Formie::$plugin->getPermissions();
        // Craft stores permission names lowercased and matches them case-sensitively when
        // reading them back, so any rows we create here must already be lowercase.
        $pagePermissions = array_map('strtolower', array_keys($permissions->getSettingsPermissionDefinitions()));

        $userIds = (new Query())
            ->select(['upu.userId'])
            ->from(['upu' => Table::USERPERMISSIONS_USERS])
            ->innerJoin(['up' => Table::USERPERMISSIONS], '[[up.id]] = [[upu.permissionId]]')
            ->where(['up.name' => strtolower(Permissions::PERM_ACCESS_SETTINGS)])
            ->column();

        $userIds = array_unique(array_map('intval', $userIds));

        if ($userIds === []) {
            return true;
        }

        $permissionIdsByName = [];

        foreach ($pagePermissions as $permissionName) {
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

            $permissionIdsByName[$permissionName] = (int)$permissionId;
        }

        foreach ($userIds as $userId) {
            $existingPermissionIds = (new Query())
                ->select(['upu.permissionId'])
                ->from(['upu' => Table::USERPERMISSIONS_USERS])
                ->where(['upu.userId' => $userId])
                ->column();

            $insert = [];

            foreach ($permissionIdsByName as $permissionId) {
                if (in_array($permissionId, $existingPermissionIds, true)) {
                    continue;
                }

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
        $permissions = Formie::$plugin->getPermissions();

        $this->delete(Table::USERPERMISSIONS, [
            'name' => array_map('strtolower', array_keys($permissions->getSettingsPermissionDefinitions())),
        ]);

        return true;
    }
}
