<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table as FormieTable;
use verbb\formie\integrations\elements\Entry as EntryIntegration;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;

/**
 * Formie 2 and Formie 3 both have a migration named `m241128_100000_entry_integrations`. A Formie 2 site has already
 * recorded the name, so upgrading runs the conversion to `sectionId:entryTypeId` but never the one that follows it to UIDs.
 */
class m261104_040000_entry_destination_uids extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $handles = (new Query())
            ->select(['handle'])
            ->from(FormieTable::FORMIE_INTEGRATIONS)
            ->where(['type' => EntryIntegration::class])
            ->column($this->db);

        if (!$handles) {
            return true;
        }

        foreach ((new Query())->select(['id', 'settings'])->from(FormieTable::FORMIE_FORMS)->each(100, $this->db) as $row) {
            $settings = Json::decodeIfJson($row['settings']);

            if (!is_array($settings)) {
                continue;
            }

            $changed = false;

            foreach ($handles as $handle) {
                $selection = $settings['integrations'][$handle]['entryTypeSection'] ?? null;
                $uids = is_string($selection) ? $this->_uidsForSelection($selection) : null;

                if ($uids) {
                    $settings['integrations'][$handle]['entryTypeSection'] = $uids;
                    $changed = true;

                    // This is the last trace of which bindings were affected, so keep one for anyone checking past deliveries.
                    $message = "Form {$row['id']}: Entry integration `{$handle}` now uses `{$uids}` (was `{$selection}`). Entries it created while this selection was stored may be missing mapped field content.";
                    Craft::warning($message, __METHOD__);
                    echo "    > {$message}\n";
                }
            }

            if ($changed) {
                $this->update(FormieTable::FORMIE_FORMS, ['settings' => Json::encode($settings)], ['id' => $row['id']], [], false);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261104_040000_entry_destination_uids cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _uidsForSelection(string $selection): ?string
    {
        if (!preg_match('/^(\d+):(\d+)$/D', $selection, $matches)) {
            return null;
        }

        // Only a pair that still belongs together is the destination the form was configured with.
        $uids = (new Query())
            ->select(['sectionUid' => 'sections.uid', 'typeUid' => 'entrytypes.uid'])
            ->from(['pairs' => Table::SECTIONS_ENTRYTYPES])
            ->innerJoin(['sections' => Table::SECTIONS], '[[sections.id]] = [[pairs.sectionId]]')
            ->innerJoin(['entrytypes' => Table::ENTRYTYPES], '[[entrytypes.id]] = [[pairs.typeId]]')
            ->where(['pairs.sectionId' => (int)$matches[1], 'pairs.typeId' => (int)$matches[2]])
            ->one($this->db);

        return $uids ? $uids['sectionUid'] . ':' . $uids['typeUid'] : null;
    }
}
