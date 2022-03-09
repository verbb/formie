<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;

class Token extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%formie_tokens}}';
    }
}
