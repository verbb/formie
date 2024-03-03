<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m231130_000000_conditions_mapping extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $fields = (new Query())
            ->select(['*'])
            ->from([Table::FORMIE_FIELDS])
            ->all();

        foreach ($fields as $field) {
            $settings = Json::decode($field['settings']);
            $conditionsSettings = $settings['conditions'] ?? [];
            $hasChanged = false;

            if (is_array($conditionsSettings) && $conditionsSettings) {
                $conditions = $conditionsSettings['conditions'] ?? [];

                if (is_array($conditions) && $conditions) {
                    foreach ($conditions as $conditionKey => $condition) {
                        $conditionField = $condition['field'] ?? null;

                        if (is_string($conditionField)) {
                            // Rename any old array-like syntax `group[nested][field]` with dot-notation `group.nested.field`
                            if (str_contains($conditionField, '[')) {
                                $hasChanged = true;
                                $conditionsSettings['conditions'][$conditionKey]['field'] = $conditionField = str_replace(['[', ']'], ['.', ''], $conditionField);
                            }

                            // Rename `{*}` to `{field:*}` - but watch out for `{submission:*}`
                            if (str_starts_with($conditionField, '{') && !str_starts_with($conditionField, '{submission:') && !str_starts_with($conditionField, '{field:')) {
                                $hasChanged = true;
                                $conditionsSettings['conditions'][$conditionKey]['field'] = $conditionField = str_replace('{', '{field:', $conditionField);
                            }
                        }
                    }
                }
            }

            if ($hasChanged) {
                $settings['conditions'] = $conditionsSettings;

                $this->update(Table::FORMIE_FIELDS, [
                    'settings' => Json::encode($settings),
                ], ['id' => $field['id']], [], false);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m231130_000000_conditions_mapping cannot be reverted.\n";

        return false;
    }
}
