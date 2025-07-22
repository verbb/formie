<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
use verbb\formie\fields\formfields\Date;
use verbb\formie\integrations\elements\User as UserIntegration;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class m241128_000000_user_group_integrations extends Migration
{
    public function safeUp(): bool
    {
        $userIntegrations = (new Query())
            ->select(['*'])
            ->from(['{{%formie_integrations}}'])
            ->where(['type' => UserIntegration::class])
            ->all();

        foreach ($userIntegrations as $userIntegration) {
            $forms = (new Query())
                ->select(['*'])
                ->from(['{{%formie_forms}}'])
                ->all();

            foreach ($forms as $form) {
                $updatedSettings = false;
                $groupUids = [];

                $settings = Json::decode($form['settings']);
                $userIntegrationSettings = $settings['integrations'][$userIntegration['handle']] ?? [];
                $groupIds = $userIntegrationSettings['groupIds'] ?? [];

                if (is_array($groupIds)) {
                    foreach ($groupIds as $groupId) {
                        if ($groupUid = Db::uidById(Table::USERGROUPS, $groupId)) {
                            $groupUids[] = $groupUid;
                        }
                    }
                }

                if ($groupUids) {
                    $settings['integrations'][$userIntegration['handle']]['groupUids'] = $groupUids;
                    $updatedSettings = true;
                }
            }

            if ($updatedSettings) {
                $this->update('{{%formie_forms}}', ['settings' => Json::encode($settings)], ['id' => $form['id']]);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m241128_000000_user_group_integrations cannot be reverted.\n";
        return false;
    }
}
