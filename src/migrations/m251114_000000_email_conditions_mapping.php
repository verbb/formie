<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m251114_000000_email_conditions_mapping extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $notifications = (new Query())
            ->select(['*'])
            ->from([Table::FORMIE_NOTIFICATIONS])
            ->all();

        foreach ($notifications as $notification) {
            $conditionsSettings = Json::decode($notification['conditions']);
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
                                $conditionsSettings['conditions'][$conditionKey]['field'] = str_replace(['[', ']'], ['.', ''], $conditionField);
                            }

                            // Rename `{*}` to `{field:*}` - but watch out for `{submission:*}`
                            if (str_starts_with($conditionField, '{') && !str_starts_with($conditionField, '{submission:') && !str_starts_with($conditionField, '{field:')) {
                                $hasChanged = true;
                                $conditionsSettings['conditions'][$conditionKey]['field'] = str_replace('{', '{field:', $conditionField);
                            }
                        }
                    }
                }
            }

            if ($hasChanged) {
                $settings['conditions'] = $conditionsSettings;

                $this->update(Table::FORMIE_NOTIFICATIONS, [
                    'conditions' => Json::encode($conditionsSettings),
                ], ['id' => $notification['id']], [], false);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m251114_000000_email_conditions_mapping cannot be reverted.\n";

        return false;
    }
}
