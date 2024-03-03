<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;

class FieldLayoutRow extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%formie_fieldlayout_rows}}';
    }
}
