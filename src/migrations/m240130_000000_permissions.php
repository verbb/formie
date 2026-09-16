<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;

class m240130_000000_permissions extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $toUpdate = [
            'formie-viewForms' => ['formie-accessForms'],
            'formie-viewSubmissions' => ['formie-accessSubmissions'],
            'formie-viewSentNotifications' => ['formie-accessSentNotifications', 'formie-resendSentNotifications', 'formie-deleteSentNotifications'],

            'formie-editSubmissions' => ['formie-viewSubmissions', 'formie-createSubmissions', 'formie-saveSubmissions', 'formie-deleteSubmissions'],

            'formie-editForms' => ['formie-manageForms'],
            'formie-manageFormAppearance' => ['formie-showFormAppearance'],
            'formie-manageFormBehavior' => ['formie-showFormBehavior'],
            'formie-manageNotifications' => ['formie-showNotifications'],
            'formie-manageNotificationsAdvanced' => ['formie-showNotificationsAdvanced'],
            'formie-manageNotificationsTemplates' => ['formie-showNotificationsTemplates'],
            'formie-manageFormIntegrations' => ['formie-showFormIntegrations'],
            'formie-manageFormUsage' => ['formie-showFormUsage'],
            'formie-manageFormSettings' => ['formie-showFormSettings'],
        ];

        foreach ((new Query())->select(['elements.uid'])->from(['forms' => Table::FORMIE_FORMS])->innerJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[forms.id]]')->all() as $form) {
            $suffix = ':' . $form['uid'];

            $toUpdate += [
                "formie-manageSubmission{$suffix}" => ["formie-viewSubmissions{$suffix}", "formie-createSubmissions{$suffix}", "formie-saveSubmissions{$suffix}", "formie-deleteSubmissions{$suffix}"],

                "formie-manageForm{$suffix}" => ["formie-manageForms{$suffix}"],
                "formie-manageFormAppearance{$suffix}" => ["formie-showFormAppearance{$suffix}"],
                "formie-manageFormBehavior{$suffix}" => ["formie-showFormBehavior{$suffix}"],
                "formie-manageNotifications{$suffix}" => ["formie-showNotifications{$suffix}"],
                "formie-manageNotificationsAdvanced{$suffix}" => ["formie-showNotificationsAdvanced{$suffix}"],
                "formie-manageNotificationsTemplates{$suffix}" => ["formie-showNotificationsTemplates{$suffix}"],
                "formie-manageFormIntegrations{$suffix}" => ["formie-showFormIntegrations{$suffix}"],
                "formie-manageFormUsage{$suffix}" => ["formie-showFormUsage{$suffix}"],
                "formie-manageFormSettings{$suffix}" => ["formie-showFormSettings{$suffix}"],
            ];
        }

        // Lowercase everything
        $toUpdate = array_combine(
            array_map('strtolower', array_keys($toUpdate)),
            array_map(fn($newPermissions) => array_map('strtolower', $newPermissions), array_values($toUpdate)));

        // Snapshot both direct and inherited grants before changing names: the old
        // submissions navigation permission now has the meaning "view every form".
        $grants = [];

        foreach ([Table::USERPERMISSIONS_USERS => 'userId', Table::USERPERMISSIONS_USERGROUPS => 'groupId'] as $table => $ownerColumn) {
            $rows = (new Query())
                ->select(['up.name', 'ownerId' => 'link.' . $ownerColumn])
                ->from(['link' => $table])
                ->innerJoin(['up' => Table::USERPERMISSIONS], '[[up.id]] = [[link.permissionId]]')
                ->where(['up.name' => array_keys($toUpdate)])
                ->all($this->db);

            foreach ($rows as $row) {
                $targets = $toUpdate[$row['name']];

                if ($row['name'] === strtolower('formie-viewSentNotifications')) {
                    $targets[] = $row['name'];
                }

                foreach ($targets as $name) {
                    $grants[$table][$ownerColumn][$row['ownerId']][$name] = true;
                }
            }
        }

        $this->delete(Table::USERPERMISSIONS, ['name' => array_keys($toUpdate)]);

        foreach ($grants as $table => $columns) {
            foreach ($columns as $ownerColumn => $owners) {
                foreach ($owners as $ownerId => $names) {
                    foreach (array_keys($names) as $name) {
                        $permissionId = (new Query())->select('id')->from(Table::USERPERMISSIONS)->where(['name' => $name])->scalar($this->db);

                        if (!$permissionId) {
                            $this->insert(Table::USERPERMISSIONS, ['name' => $name]);
                            $permissionId = $this->db->getLastInsertID();
                        }

                        $link = ['permissionId' => $permissionId, $ownerColumn => $ownerId];

                        if (!(new Query())->from($table)->where($link)->exists($this->db)) {
                            $this->insert($table, $link);
                        }
                    }
                }
            }
        }

        // Group grants also live in project config; keep its source of truth in sync
        // so a later config apply cannot restore the obsolete broader permission.
        $projectConfig = Craft::$app->getProjectConfig();

        foreach ($projectConfig->get('users.groups') ?? [] as $uid => $group) {
            $original = $group['permissions'] ?? [];
            $mapped = [];

            foreach ($original as $name) {
                $name = strtolower($name);
                $mapped = array_merge($mapped, $toUpdate[$name] ?? [$name]);

                if ($name === strtolower('formie-viewSentNotifications')) {
                    $mapped[] = $name;
                }
            }

            $mapped = array_values(array_unique($mapped));
            sort($mapped);

            if ($mapped !== $original) {
                $projectConfig->set('users.groups.' . $uid . '.permissions', $mapped, 'Migrate Formie group permissions');
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m240130_000000_permissions cannot be reverted.\n";

        return false;
    }
}
