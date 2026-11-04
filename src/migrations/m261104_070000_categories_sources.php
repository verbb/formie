<?php
namespace verbb\formie\migrations;

use verbb\formie\fields\Categories;
use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\helpers\Json;

use DateTime;
use DateTimeZone;

class m261104_070000_categories_sources extends Migration
{
    // Constants
    // =========================================================================

    private const CRAFT5_CONVERSION = 'm231125_000000_craft5';
    private const FIRST_FORMIE4_MIGRATION = 'm260418_000000_field_definitions_and_form_fields';
    private const SAME_UPGRADE_GAP = 3600;
    private const FRESH_INSTALL_GAP = 3600;


    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Formie 2 offered a Categories field's source list and ignored the single source saved beside it. Formie 3
        // and 4 read that source instead, and saved fields hold the same values as a single-source Formie 4 field.
        // Only the applied history tells them apart, so only sites that came straight from Formie 2 change.
        $upgradeEnd = $this->_formie2UpgradeEnd();

        if ($upgradeEnd === null) {
            return true;
        }

        $fields = (new Query())
            ->select(['id', 'settings', 'dateCreated', 'dateUpdated'])
            ->from(Table::FORMIE_FIELDS)
            ->where(['type' => Categories::class])
            ->all();

        foreach ($fields as $field) {
            // Anything created or edited since the upgrade was authored in Formie 4.
            if ($this->_timestamp($field['dateCreated']) > $upgradeEnd || $this->_timestamp($field['dateUpdated']) > $upgradeEnd) {
                continue;
            }

            $settings = Json::decodeIfJson($field['settings']);

            if (!is_array($settings) || ($settings['allowMultipleSources'] ?? null) !== false || empty($settings['source']) || empty($settings['sources'])) {
                continue;
            }

            $settings['allowMultipleSources'] = true;

            $this->update(Table::FORMIE_FIELDS, ['settings' => Json::encode($settings)], ['id' => $field['id']], [], false);
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261104_070000_categories_sources cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _formie2UpgradeEnd(): ?int
    {
        $history = (new Query())
            ->select(['applyTime'])
            ->from(CraftTable::MIGRATIONS)
            ->where(['track' => 'plugin:formie'])
            ->indexBy('name')
            ->orderBy(['applyTime' => SORT_ASC, 'name' => SORT_ASC])
            ->column();

        if (!isset($history[self::CRAFT5_CONVERSION], $history[self::FIRST_FORMIE4_MIGRATION])) {
            return null;
        }

        $conversion = $this->_timestamp($history[self::CRAFT5_CONVERSION]);

        // Installing Formie 3 or 4 marks every migration as applied, but there is nothing to restore.
        $installDate = (new Query())->select(['installDate'])->from(CraftTable::PLUGINS)->where(['handle' => 'formie'])->scalar();

        if (!$installDate || $conversion - $this->_timestamp($installDate) < self::FRESH_INSTALL_GAP) {
            return null;
        }

        // Follow the upgrade from the conversion until the migrations stop running together.
        $upgradeEnd = $conversion;
        $reachedFormie4 = false;

        foreach ($history as $name => $applyTime) {
            $time = $this->_timestamp($applyTime);

            if ($time < $conversion) {
                continue;
            }

            if ($time - $upgradeEnd > self::SAME_UPGRADE_GAP) {
                break;
            }

            $upgradeEnd = $time;
            $reachedFormie4 = $reachedFormie4 || $name === self::FIRST_FORMIE4_MIGRATION;
        }

        return $reachedFormie4 ? $upgradeEnd : null;
    }

    private function _timestamp(string $date): int
    {
        return (new DateTime($date, new DateTimeZone('UTC')))->getTimestamp();
    }
}
