<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class m261104_010000_dedicated_permission_settings extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Per-form grants keep working at runtime, but Craft discards any permission it
        // can't find registered whenever a user or group is saved, and forms only register
        // their permissions once they use dedicated permissions. Turning the setting on
        // where a grant already exists keeps it, without giving anyone new access.
        $uids = $this->_getGrantedFormUids();

        if (!$uids) {
            return true;
        }

        $forms = (new Query())
            ->select(['forms.id', 'forms.settings'])
            ->from(['forms' => Table::FORMIE_FORMS])
            ->innerJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[forms.id]]')
            ->where(['elements.uid' => $uids])
            ->all($this->db);

        foreach ($forms as $form) {
            $settings = Json::decodeIfJson($form['settings'] ?? '');

            if (!is_array($settings) || !empty($settings['usePerFormPermissions'])) {
                continue;
            }

            $settings['usePerFormPermissions'] = true;

            $this->update(Table::FORMIE_FORMS, ['settings' => Json::encode($settings)], ['id' => $form['id']], [], false);
        }

        if (method_exists(Craft::$app->getUserPermissions(), 'reset')) {
            Craft::$app->getUserPermissions()->reset();
        }

        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }


    // Private Methods
    // =========================================================================

    private function _getGrantedFormUids(): array
    {
        $names = [];

        foreach ([Table::USERPERMISSIONS_USERS, Table::USERPERMISSIONS_USERGROUPS] as $table) {
            $names[] = (new Query())
                ->select(['permissions.name'])
                ->distinct()
                ->from(['permissions' => Table::USERPERMISSIONS])
                ->innerJoin(['grants' => $table], '[[grants.permissionId]] = [[permissions.id]]')
                ->where(['like', 'permissions.name', 'formie-%:%', false])
                ->column($this->db);
        }

        // Project config is what group permissions are deployed from, so it counts too.
        foreach (Craft::$app->getProjectConfig()->get('users.groups') ?? [] as $group) {
            $names[] = array_filter($group['permissions'] ?? [], fn($name) => is_string($name) && str_starts_with(strtolower($name), 'formie-'));
        }

        $uids = [];

        foreach (array_merge(...$names) as $name) {
            $scope = explode(':', $name, 2)[1] ?? '';

            if (StringHelper::isUUID($scope)) {
                $uids[strtolower($scope)] = true;
            }
        }

        return array_keys($uids);
    }
}
