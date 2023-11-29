<?php
namespace verbb\formie\migrations;

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
            ->from(['{{%fields}}'])
            ->all();

        foreach ($fields as $field) {
            if (!str_contains($field['context'], 'formie')) {
                continue;
            }

            $settings = Json::decode($field['settings']);
            $conditionsSettings = $settings['conditions'] ?? [];
            $hasChanged = false;

            if (is_array($conditionsSettings) && $conditionsSettings) {
                $conditions = $conditionsSettings['conditions'] ?? [];

                if (is_array($conditions) && $conditions) {
                    foreach ($conditions as $conditionKey => $condition) {
                        $field = $condition['field'] ?? null;

                        // Rename any old array-like syntax `group[nested][field]` with dot-notation `group.nested.field`
                        if (str_contains($field, '[')) {
                            $hasChanged = true;
                            $conditionsSettings['conditions'][$conditionKey]['field'] = $field = str_replace(['[', ']'], ['.', ''], $field);
                        }

                        // Rename `{*}` to `{field:*}` - but watch out for `{submission:*}`
                        if (str_starts_with($field, '{') && !str_starts_with($field, '{submission:') && !str_starts_with($field, '{field:')) {
                            $hasChanged = true;
                            $conditionsSettings['conditions'][$conditionKey]['field'] = $field = str_replace('{', '{field:', $field);
                        }
                    }
                }
            }

            if ($hasChanged) {
                $settings['conditions'] = $conditionsSettings;

                $this->update('{{%fields}}', [
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
