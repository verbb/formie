<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;

class m261020_010000_permission_cleanup extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Keep historical mappings explicit: future permission registrations must not
        // change the access granted when this migration runs during an older upgrade.
        $mapping = [
            'formie-accesssettings' => [
                'formie-accesssettingssection', 'formie-settingsencryption', 'formie-settingsimportexport',
                'formie-settingssyncedfields', 'formie-settingsscheduledreports', 'formie-settingssupport',
                'formie-settingsmigratefreeform4', 'formie-settingsmigratefreeform5', 'formie-settingsmigratesproutforms',
                'formie-accessspamprotection', 'formie-managespamrules', 'formie-managecaptchas',
            ],
            'formie-managescheduledreports' => [
                'formie-accesssettingssection', 'formie-settingsscheduledreports', 'formie-testscheduledreports',
            ],
            'formie-settingsspam' => ['formie-accessspamprotection', 'formie-managespamrules', 'formie-managecaptchas'],
            'formie-settingscaptchas' => ['formie-accessspamprotection', 'formie-managespamrules', 'formie-managecaptchas'],
            'formie-settingsspamprotection' => ['formie-accessspamprotection', 'formie-managespamrules', 'formie-managecaptchas'],
            'formie-settingsstatuses' => [],
            'formie-settingsgeneral' => [],
            'formie-settingsforms' => [],
            'formie-settingsformgroups' => [],
            'formie-settingsdefaults' => [],
            'formie-settingsfields' => [],
            'formie-settingsnotifications' => [],
            'formie-settingssentnotifications' => [],
            'formie-settingssubmissionstatuses' => [],
            'formie-settingsformstatuses' => [],
            'formie-settingssubmissions' => [],
            'formie-settingsintegrationssettings' => [],
            'formie-settingsformtemplates' => [],
            'formie-settingsemailtemplates' => [],
            'formie-settingspdftemplates' => [],
        ];

        $retired = array_keys($mapping);

        // Retained capabilities need their navigation ancestors to survive editor saves.
        // Include every ancestor directly, without relying on intermediate grants.
        $parents = [
            'formie-settingsencryption' => ['formie-accesssettingssection'],
            'formie-settingsimportexport' => ['formie-accesssettingssection'],
            'formie-settingssyncedfields' => ['formie-accesssettingssection'],
            'formie-settingsscheduledreports' => ['formie-accesssettingssection'],
            'formie-settingssupport' => ['formie-accesssettingssection'],
            'formie-settingsmigratefreeform4' => ['formie-accesssettingssection'],
            'formie-settingsmigratefreeform5' => ['formie-accesssettingssection'],
            'formie-settingsmigratesproutforms' => ['formie-accesssettingssection'],
            'formie-importforms' => ['formie-accesssettingssection', 'formie-settingsimportexport'],
            'formie-exportforms' => ['formie-accesssettingssection', 'formie-settingsimportexport'],
            'formie-accessstencils' => ['formie-accessstencilssection'],
            'formie-accessintegrations' => ['formie-accessintegrationssection'],
            'formie-managecaptchas' => ['formie-accessspamprotection'],
            'formie-managespamrules' => ['formie-accessspamprotection'],
        ];

        foreach ($parents as $permission => $ancestors) {
            $mapping[$permission] = [$permission, ...$ancestors];
        }

        foreach ($mapping as $source => $targets) {
            $sourceIds = (new Query())->select('id')->from(Table::USERPERMISSIONS)->where(['name' => $source])->column($this->db);

            if (!$sourceIds) {
                continue;
            }

            foreach ($targets as $target) {
                $this->db->createCommand()->upsert(Table::USERPERMISSIONS, ['name' => $target], false)->execute();
                $targetId = (new Query())->select('id')->from(Table::USERPERMISSIONS)->where(['name' => $target])->scalar($this->db);

                foreach ([Table::USERPERMISSIONS_USERS => 'userId', Table::USERPERMISSIONS_USERGROUPS => 'groupId'] as $table => $column) {
                    $ownerIds = (new Query())->select($column)->from($table)->where(['permissionId' => $sourceIds])->distinct()->column($this->db);

                    foreach ($ownerIds as $ownerId) {
                        $this->db->createCommand()->upsert($table, ['permissionId' => $targetId, $column => $ownerId], false)->execute();
                    }
                }
            }
        }

        // Group permissions are deployed through project config. Transform that source
        // too, so applying it later cannot restore obsolete checkboxes or lose grants.
        $projectConfig = Craft::$app->getProjectConfig();
        $readOnly = $projectConfig->readOnly;
        $projectConfig->readOnly = false;

        try {
            foreach ($projectConfig->get('users.groups') ?? [] as $uid => $group) {
                $original = $group['permissions'] ?? [];
                $mapped = [];
                $changed = false;

                foreach ($original as $name) {
                    $targets = $mapping[strtolower($name)] ?? null;
                    $changed = $changed || $targets !== null;
                    array_push($mapped, ...($targets ?? [$name]));
                }

                if ($changed) {
                    $mapped = array_values(array_unique(array_map('strtolower', $mapped)));
                    sort($mapped);
                    $projectConfig->set('users.groups.' . $uid . '.permissions', $mapped, 'Migrate Formie production settings permissions');
                }
            }
        } finally {
            $projectConfig->readOnly = $readOnly;
        }

        $this->delete(Table::USERPERMISSIONS, ['name' => $retired]);

        if (method_exists(Craft::$app->getUserPermissions(), 'reset')) {
            Craft::$app->getUserPermissions()->reset();
        }

        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }
}
