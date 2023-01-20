<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\elements\Form;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;

class m221206_000000_fix_syncs extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $forms = Form::find()->status(null)->all();

        // Correct any incorrect fields referencing the wrong form
        foreach ($forms as $form) {
            $fields = $form->getCustomFields();

            if ($fields) {
                foreach ($fields as $field) {
                    $fieldRow = (new Query())
                        ->select(['id', 'handle', 'settings'])
                        ->from(['{{%fields}}'])
                        ->where(['id' => $field->id])
                        ->one();

                    if ($fieldRow) {
                        $settings = Json::decode($fieldRow['settings']);

                        $settings['formId'] = $form['id'];

                        $this->update(Table::FIELDS, [
                            'settings' => Json::encode($settings)
                        ], ['id' => $fieldRow['id']], [], false);

                        echo "    > Reset `formId` for field" . $fieldRow['handle']  . PHP_EOL;
                    }
                }
            }

            $contentTable = Formie::$plugin->getForms()->defineContentTableName($form);

            // For each content table, cleanup any columns that don't reference a field (for this form)
            $table = $this->db->getTableSchema($contentTable);

            foreach ($table->getColumnNames() as $columnName) {
                if (str_starts_with($columnName, 'field_')) {
                    // Find a field that matches this. But be careful, because people can use underscores in the field handles.
                    $columnParts = explode('_', $columnName);
                    $prefix = $columnParts[0] ?? null;
                    $handle = $columnParts[1] ?? null;
                    $suffix = $columnParts[2] ?? null;

                    // If larger than 3, that means the field handle contains an underscore. It's pretty unreliable to determine
                    // the field handle from this, as it's not guaranteed that the column has a field suffix (older Craft installs)
                    // So best to just skip this field check, rather than risking deletion.
                    if (count($columnParts) > 3) {
                        continue;
                    }

                    $columnField = (new Query())
                        ->select(['id'])
                        ->from(['{{%fields}}'])
                        ->where(['handle' => $handle, 'columnSuffix' => $suffix, 'context' => "formie:$form->uid"])
                        ->exists();

                    if (!$columnField) {
                        $this->dropColumn($contentTable, $columnName);

                        echo "    > Dropped column $columnName in $contentTable."  . PHP_EOL;
                    }
                }
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m221206_000000_fix_syncs cannot be reverted.\n";
        return false;
    }
}
