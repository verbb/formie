<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\services\Permissions;

use Craft;
use craft\db\Migration;
use craft\db\Query;

class m260615_000000_group_permissions extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $permissions = Formie::$plugin->getPermissions();
        $groupHandlesById = (new Query())
            ->select(['id', 'handle'])
            ->from([Table::FORMIE_FORM_GROUPS])
            ->indexBy('id')
            ->all($this->db);

        $forms = (new Query())
            ->select(['uid', 'groupId'])
            ->from([Table::FORMIE_FORMS])
            ->all($this->db);

        $groupHandleByUid = [];

        foreach ($forms as $form) {
            $groupId = (int)($form['groupId'] ?? 0);
            $groupHandleByUid[(string)$form['uid']] = $groupId && isset($groupHandlesById[$groupId])
                ? (string)$groupHandlesById[$groupId]['handle']
                : Permissions::GROUP_UNGROUPED;
        }

        $permissionMap = [
            Permissions::PERM_MANAGE_FORMS => Permissions::PERM_MANAGE_FORMS,
            Permissions::PERM_VIEW_SUBMISSIONS => Permissions::PERM_VIEW_SUBMISSIONS,
            'formie-createSubmissions' => 'formie-createSubmissions',
            'formie-saveSubmissions' => 'formie-saveSubmissions',
            'formie-deleteSubmissions' => 'formie-deleteSubmissions',
            Permissions::PERM_VIEW_SENT_NOTIFICATIONS => Permissions::PERM_VIEW_SENT_NOTIFICATIONS,
            'formie-resendSentNotifications' => 'formie-resendSentNotifications',
            'formie-deleteSentNotifications' => 'formie-deleteSentNotifications',
            'formie-showFormAppearance' => 'formie-showFormAppearance',
            'formie-showFormBehavior' => 'formie-showFormBehavior',
            'formie-showNotifications' => 'formie-showNotifications',
            'formie-showNotificationsAdvanced' => 'formie-showNotificationsAdvanced',
            'formie-showNotificationsTemplates' => 'formie-showNotificationsTemplates',
            'formie-showFormIntegrations' => 'formie-showFormIntegrations',
            'formie-showFormUsage' => 'formie-showFormUsage',
            'formie-showFormSettings' => 'formie-showFormSettings',
        ];

        foreach ($permissionMap as $basePermission => $targetBasePermission) {
            $this->_migrateScopedPermissions($basePermission, $targetBasePermission, $groupHandleByUid, $permissions);
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260615_000000_group_permissions cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _migrateScopedPermissions(string $basePermission, string $targetBasePermission, array $groupHandleByUid, Permissions $permissions): void
    {
        $rows = (new Query())
            ->select(['up.name', 'upu.userId'])
            ->from(['up' => Table::USERPERMISSIONS])
            ->innerJoin(['upu' => Table::USERPERMISSIONS_USERS], '[[upu.permissionId]] = [[up.id]]')
            ->where(['like', 'up.name', $basePermission . ':', false])
            ->andWhere(['not like', 'up.name', $basePermission . ':group:', false])
            ->all($this->db);

        $grantsByUser = [];

        foreach ($rows as $row) {
            $permissionName = (string)($row['name'] ?? '');
            $userId = (int)($row['userId'] ?? 0);
            $scope = substr($permissionName, strlen($basePermission) + 1);

            if (!$userId || $scope === '') {
                continue;
            }

            $groupHandle = $groupHandleByUid[$scope] ?? Permissions::GROUP_UNGROUPED;
            $targetPermission = $permissions->scopedPermission($targetBasePermission, $permissions->groupScope($groupHandle));
            $grantsByUser[$userId][$targetPermission] = true;
        }

        foreach ($grantsByUser as $userId => $targetPermissions) {
            $existingPermissions = Craft::$app->getUserPermissions()->getPermissionsByUserId($userId);
            $mergedPermissions = array_values(array_unique(array_merge($existingPermissions, array_keys($targetPermissions))));
            Craft::$app->getUserPermissions()->saveUserPermissions($userId, $mergedPermissions);
        }
    }
}
