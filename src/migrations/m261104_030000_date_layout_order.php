<?php
namespace verbb\formie\migrations;

use verbb\formie\fields\Date;
use verbb\formie\helpers\MigrationHelper;
use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m261104_030000_date_layout_order extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        MigrationHelper::ensureFieldDefinitions($this->db);

        $fields = (new Query())
            ->select(['id', 'settings'])
            ->from([Table::FORMIE_FIELDS])
            ->where(['type' => Date::class])
            ->all();

        foreach ($fields as $field) {
            $settings = Json::decode($field['settings']) ?? [];
            $displayType = $settings['displayType'] ?? null;
            $nestedLayoutId = $settings['nestedLayoutId'] ?? null;
            $storedRows = $settings['layouts'][$displayType] ?? null;

            if (!$nestedLayoutId || !in_array($displayType, ['dropdowns', 'inputs'], true) || !is_array($storedRows) || count($storedRows) !== 1) {
                continue;
            }

            // Saving a form reapplies this stored copy of the sub-fields, so it needs to match what's rendered. An earlier
            // repair reordered the sub-fields without updating it, and a later unchanged save would put them back.
            $placements = (new Query())
                ->select(['handle' => 'f.handle', 'rowId' => 'ff.rowId'])
                ->from(['ff' => Table::FORMIE_FORM_FIELDS])
                ->innerJoin(['f' => Table::FORMIE_FIELDS], '[[f.id]] = [[ff.fieldId]]')
                ->where(['ff.layoutId' => (int)$nestedLayoutId])
                ->orderBy(['ff.sortOrder' => SORT_ASC, 'ff.id' => SORT_ASC])
                ->all();

            $order = array_column($placements, 'handle');
            $storedFields = $storedRows[0]['fields'] ?? [];
            $storedOrder = array_map(fn($storedField) => $storedField['handle'] ?? $storedField['settings']['handle'] ?? null, is_array($storedFields) ? $storedFields : []);

            // Anything else has been arranged by hand, so there's no single order to align with.
            if ($order === $storedOrder || count(array_unique(array_column($placements, 'rowId'))) !== 1 || array_diff($order, $storedOrder) || array_diff($storedOrder, $order)) {
                continue;
            }

            $fieldsByHandle = array_combine($storedOrder, $storedFields);
            $settings['layouts'][$displayType][0]['fields'] = array_map(fn($handle) => $fieldsByHandle[$handle], $order);

            $this->update(Table::FORMIE_FIELDS, ['settings' => Json::encode($settings)], ['id' => $field['id']], [], false);
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261104_030000_date_layout_order cannot be reverted.\n";

        return false;
    }
}
