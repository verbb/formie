<?php
namespace verbb\formie\migrations;

use verbb\formie\fields\Calculations;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m250113_000000_calculations extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $fields = (new Query())
            ->select(['*'])
            ->from([Table::FORMIE_FIELDS])
            ->where(['type' => Calculations::class])
            ->all();

        foreach ($fields as $field) {
            $settings = Json::decode($field['settings']);
            $formula = $settings['formula'] ?? [];
            $hasChanged = false;

            // As `formula` is rich text, it's far easier to parse as a JSON string, so convert to JSON, then back again.
            if (is_array($formula) && $formula) {
                $formulaString = Json::encode($formula);

                if (is_string($formulaString) && str_contains($formulaString, '{field.')) {
                    $hasChanged = true;

                    $formulaString = str_replace('{field.', '{field:', $formulaString);
                }

                if ($hasChanged) {
                    $settings['formula'] = Json::decode($formulaString);

                    $this->update(Table::FORMIE_FIELDS, [
                        'settings' => Json::encode($settings),
                    ], ['id' => $field['id']], [], false);
                }
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m250113_000000_calculations cannot be reverted.\n";

        return false;
    }
}
