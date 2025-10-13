<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\fields;
use verbb\formie\fields\subfields;
use verbb\formie\models\FieldLayout;
use verbb\formie\positions\Hidden as HiddenPosition;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m251014_000000_date_subfields extends Migration
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
            $updatedFields = false;
            $fieldLayout = null;
            $settings = Json::decode($field['settings']);

            if ($field['type'] === fields\Date::class) {
                $displayType = $settings['displayType'] ?? 'calendar';

                if ($displayType == 'calendar' || $displayType == 'datePicker') {
                    // Get the field layout for the field, or if it already exists with fields, skip
                    $fieldLayout = $this->_getFieldLayout($settings);
                    $fields = $fieldLayout->getFields();

                    foreach ($fields as $subField) {
                        if ($subField instanceof fields\SingleLineText) {
                            if ($subField->handle === 'date') {
                                $this->update(Table::FORMIE_FIELDS, ['type' => subfields\DateDate::class], ['id' => $subField->id], [], false);
                            }

                            if ($subField->handle === 'time') {
                                $this->update(Table::FORMIE_FIELDS, ['type' => subfields\DateTime::class], ['id' => $subField->id], [], false);
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m251014_000000_date_subfields cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _getFieldLayout(array $settings): FieldLayout
    {
        $fieldLayout = null;
        $nestedLayoutId = $settings['nestedLayoutId'] ?? null;

        if ($nestedLayoutId) {
            $fieldLayout = Formie::$plugin->getFields()->getLayoutById($nestedLayoutId);
        }

        return $fieldLayout ?? new FieldLayout();
    }
}
