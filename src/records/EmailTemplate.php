<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;

class EmailTemplate extends ActiveRecord
{
    // Traits
    // =========================================================================

    use SoftDeleteTrait;


    // Static Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%formie_emailtemplates}}';
    }
}
