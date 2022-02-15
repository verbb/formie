<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
use verbb\formie\fields\formfields\Phone;
use verbb\formie\prosemirror\toprosemirror\Renderer;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class m210607_000000_permissions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $permissionIds = [];

        $this->upsert('{{%userpermissions}}', ['name' => 'formie-viewforms']);
        $permissionIds[] = $this->db->getLastInsertID('{{%userpermissions}}');

        // See which users & groups already have the "formie-manageforms" permission
        $userIds = (new Query())
            ->select(['up_u.userId'])
            ->from(['{{%userpermissions_users}} up_u'])
            ->innerJoin('{{%userpermissions}} up', '[[up.id]] = [[up_u.permissionId]]')
            ->where(['up.name' => 'formie-manageforms'])
            ->column($this->db);

        $groupIds = (new Query())
            ->select(['up_ug.groupId'])
            ->from(['{{%userpermissions_usergroups}} up_ug'])
            ->innerJoin('{{%userpermissions}} up', '[[up.id]] = [[up_ug.permissionId]]')
            ->where(['up.name' => 'formie-manageforms'])
            ->column($this->db);

        if (empty($userIds) && empty($groupIds)) {
            return;
        }

        // Assign the new permissions to the users
        if (!empty($userIds)) {
            $data = [];

            foreach ($userIds as $userId) {
                foreach ($permissionIds as $permissionId) {
                    $data[] = [$permissionId, $userId];
                }
            }

            $this->batchInsert('{{%userpermissions_users}}', ['permissionId', 'userId'], $data);
        }

        // Assign the new permissions to the groups
        if (!empty($groupIds)) {
            $data = [];

            foreach ($groupIds as $groupId) {
                foreach ($permissionIds as $permissionId) {
                    $data[] = [$permissionId, $groupId];
                }
            }

            $this->batchInsert('{{%userpermissions_usergroups}}', ['permissionId', 'groupId'], $data);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210607_000000_permissions cannot be reverted.\n";
        return false;
    }
}
