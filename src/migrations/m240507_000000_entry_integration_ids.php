<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\fields\Group;
use verbb\formie\fields\Repeater;
use verbb\formie\helpers\Table;
use verbb\formie\models\FieldLayout;
use verbb\formie\integrations\elements\Entry as EntryIntegration;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\MigrationHelper;
use craft\migrations\BaseContentRefactorMigration;

use Throwable;

class m240507_000000_entry_integration_ids extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $entryIntegrations = (new Query())
            ->select(['*'])
            ->from([Table::FORMIE_INTEGRATIONS])
            ->where(['type' => EntryIntegration::class])
            ->all();

        foreach ($entryIntegrations as $entryIntegration) {
            $forms = (new Query())
                ->select(['*'])
                ->from([Table::FORMIE_FORMS])
                ->all();

            foreach ($forms as $form) {
                $settings = Json::decode($form['settings']);
                $entryIntegrationSettings = $settings['integrations'][$entryIntegration['handle']] ?? [];
                $entryTypeId = $entryIntegrationSettings['entryTypeId'] ?? null;

                // Check if already migrated to UID in Formie 2
                $entryTypeUid = $entryIntegrationSettings['entryTypeUid'] ?? null;

                if ($entryTypeUid) {
                    $entryTypeId = Db::idByUid(Table::ENTRYTYPES, $entryTypeUid);
                }

                if (!$entryTypeId) {
                    continue;
                }

                // Check if already converted
                if (str_contains($entryTypeId, ':')) {
                    continue;
                }

                // Convert to `sectionId:entryTypeId`
                $sectionId = (new Query())
                    ->select(['sectionId'])
                    ->from([Table::SECTIONS_ENTRYTYPES])
                    ->where(['typeId' => $entryTypeId])
                    ->scalar();

                if (!$sectionId) {
                    continue;
                }

                $settings['integrations'][$entryIntegration['handle']]['entryTypeSection'] = $sectionId . ':' . $entryTypeId;

                $this->update(Table::FORMIE_FORMS, [
                    'settings' => Json::encode($settings),
                ], ['id' => $form['id']], [], false);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m240507_000000_entry_integration_ids cannot be reverted.\n";

        return false;
    }
}
