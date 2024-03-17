<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
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

        foreach (Form::find()->all() as $form) {
            $suffix = ':' . $form->uid;

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

        $toDelete = [];

        // Now add the new permissions to existing users where applicable
        foreach ($toUpdate as $oldPermission => $newPermissions) {
            $userIds = (new Query())
                ->select(['upu.userId'])
                ->from(['upu' => Table::USERPERMISSIONS_USERS])
                ->innerJoin(['up' => Table::USERPERMISSIONS], '[[up.id]] = [[upu.permissionId]]')
                ->where(['up.name' => $oldPermission])
                ->column($this->db);

            $userIds = array_unique($userIds);

            if (!empty($userIds)) {
                $insert = [];

                foreach ($newPermissions as $newPermission) {
                    $existingPermission = (new Query())
                        ->from(['up' => Table::USERPERMISSIONS])
                        ->where(['up.name' => $newPermission])
                        ->column($this->db);

                    if (!$existingPermission) {
                        $this->insert(Table::USERPERMISSIONS, [
                            'name' => $newPermission,
                        ]);

                        $newPermissionId = $this->db->getLastInsertID(Table::USERPERMISSIONS);

                        foreach ($userIds as $userId) {
                            $insert[] = [$newPermissionId, $userId];
                        }
                    }
                }

                $this->batchInsert(Table::USERPERMISSIONS_USERS, ['permissionId', 'userId'], $insert);
            }

            // Special-case not to delete some origin permissions
            if ($oldPermission === strtolower('formie-viewSentNotifications')) {
                continue;
            }

            if ($oldPermission === strtolower('formie-viewSubmissions')) {
                continue;
            }

            $toDelete[] = $oldPermission;
        }

        $this->delete(Table::USERPERMISSIONS, [
            'name' => $toDelete,
        ]);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m240130_000000_permissions cannot be reverted.\n";

        return false;
    }
}
