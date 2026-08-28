<?php
namespace verbb\formie\migrations;

use verbb\formie\fields;
use verbb\formie\fields\subfields;
use verbb\formie\helpers\DateTimeHelper;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m260828_000000_date_subfields_order extends Migration
{
    // Constants
    // =========================================================================

    public const MIGRATED_ORDER = ['year', 'month', 'day', 'hour', 'minute', 'second', 'ampm'];


    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $fields = (new Query())
            ->select(['*'])
            ->from([Table::FORMIE_FIELDS])
            ->where(['type' => fields\Date::class])
            ->all();

        foreach ($fields as $field) {
            $settings = Json::decode($field['settings']) ?? [];
            $displayType = $settings['displayType'] ?? 'calendar';
            $nestedLayoutId = $settings['nestedLayoutId'] ?? null;

            if (!$nestedLayoutId || ($displayType !== 'dropdowns' && $displayType !== 'inputs')) {
                continue;
            }

            $subFields = (new Query())
                ->select(['*'])
                ->from([Table::FORMIE_FIELDS])
                ->where(['layoutId' => $nestedLayoutId])
                ->orderBy(['sortOrder' => SORT_ASC])
                ->all();

            if (!$subFields) {
                continue;
            }

            $this->_applySortOrder($settings, $subFields);
            $this->_applyYearRange($settings, $subFields);
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260828_000000_date_subfields_order cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _applySortOrder(array $settings, array $subFields): void
    {
        // The order of subfields is defined by the parent field's date/time format, which earlier Craft 5
        // migrations didn't take into account, always producing `Y m d H i s`.
        $order = DateTimeHelper::getSubfieldOrder($settings);

        if (!$order) {
            return;
        }

        // Only touch layouts still in the order the migration produced, so any manual re-ordering is preserved
        $currentOrder = array_column($subFields, 'handle');

        if ($currentOrder !== self::MIGRATED_ORDER) {
            return;
        }

        $ranks = [];

        foreach ($subFields as $index => $subField) {
            $rank = array_search($subField['handle'], $order, true);

            // Keep any subfields not represented in the format in their existing order, at the end
            $ranks[$subField['id']] = [($rank === false ? count($order) : $rank), $index];
        }

        uasort($ranks, fn(array $a, array $b): int => $a <=> $b);

        $sortOrder = 0;

        foreach (array_keys($ranks) as $id) {
            $this->update(Table::FORMIE_FIELDS, ['sortOrder' => $sortOrder++], ['id' => $id], [], false);
        }
    }

    private function _applyYearRange(array $settings, array $subFields): void
    {
        // The year range was stored on the parent field in Craft 4, but is stored on the nested Year subfield
        // in Craft 5, which earlier migrations didn't carry across, leaving it with an invalid `100` to `100` range.
        foreach ($subFields as $subField) {
            if ($subField['type'] !== subfields\DateYearDropdown::class) {
                continue;
            }

            $subFieldSettings = Json::decode($subField['settings']) ?? [];
            $minYearRange = $subFieldSettings['minYearRange'] ?? null;
            $maxYearRange = $subFieldSettings['maxYearRange'] ?? null;

            if ($minYearRange !== null && (int)$minYearRange !== 100) {
                continue;
            }

            $subFieldSettings['minYearRange'] = (int)($settings['minYearRange'] ?? -100);
            $subFieldSettings['maxYearRange'] = (int)($settings['maxYearRange'] ?? ($maxYearRange ?? 100));

            $this->update(Table::FORMIE_FIELDS, [
                'settings' => Json::encode($subFieldSettings),
            ], ['id' => $subField['id']], [], false);
        }
    }
}
