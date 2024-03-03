<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;

class Field extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%formie_fields}}';
    }
}
