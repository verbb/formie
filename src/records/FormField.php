<?php
namespace verbb\formie\records;

use verbb\formie\helpers\Table;

use craft\db\ActiveRecord;

class FormField extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    public static function tableName(): string
    {
        return Table::FORMIE_FORM_FIELDS;
    }
}
