<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m260828_000000_page_conditions_mapping extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Page conditions were missed when field and email conditions were migrated to the new `{field:handle}` syntax
        $pages = (new Query())
            ->select(['*'])
            ->from([Table::FORMIE_FIELD_LAYOUT_PAGES])
            ->all();

        foreach ($pages as $page) {
            $settings = Json::decode($page['settings']);

            if (!is_array($settings)) {
                continue;
            }

            $hasChanged = false;

            foreach (['pageConditions', 'nextButtonConditions'] as $prop) {
                $conditionsSettings = $settings[$prop] ?? [];

                if (!is_array($conditionsSettings)) {
                    continue;
                }

                $conditions = $conditionsSettings['conditions'] ?? [];

                if (!is_array($conditions)) {
                    continue;
                }

                foreach ($conditions as $conditionKey => $condition) {
                    $conditionField = $condition['field'] ?? null;

                    if (!is_string($conditionField) || $conditionField === '') {
                        continue;
                    }

                    $newConditionField = $conditionField;

                    // Repair malformed references like `[{handle]` produced by the old syntax being read as a nested handle
                    if (str_starts_with($newConditionField, '[')) {
                        $newConditionField = trim($newConditionField, '[]');
                    }

                    // Rename any old array-like syntax `group[nested][field]` with dot-notation `group.nested.field`
                    if (str_contains($newConditionField, '[')) {
                        $newConditionField = str_replace(['[', ']'], ['.', ''], $newConditionField);
                    }

                    // Rename `{*}` to `{field:*}` - but watch out for `{submission:*}`
                    if (str_starts_with($newConditionField, '{') && !str_starts_with($newConditionField, '{submission:') && !str_starts_with($newConditionField, '{field:')) {
                        $newConditionField = str_replace('{', '{field:', $newConditionField);
                    }

                    // Handle bare handles that were never wrapped at all
                    if (!str_starts_with($newConditionField, '{')) {
                        $newConditionField = '{field:' . $newConditionField . '}';
                    }

                    if (!str_ends_with($newConditionField, '}')) {
                        $newConditionField .= '}';
                    }

                    if ($newConditionField !== $conditionField) {
                        $hasChanged = true;
                        $settings[$prop]['conditions'][$conditionKey]['field'] = $newConditionField;
                    }
                }
            }

            if ($hasChanged) {
                $this->update(Table::FORMIE_FIELD_LAYOUT_PAGES, [
                    'settings' => Json::encode($settings),
                ], ['id' => $page['id']], [], false);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260828_000000_page_conditions_mapping cannot be reverted.\n";

        return false;
    }
}
