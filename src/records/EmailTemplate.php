<?php
namespace verbb\formie\records;

use verbb\formie\helpers\Table;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;

class EmailTemplate extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    public static function tableName(): string
    {
        return Table::FORMIE_EMAIL_TEMPLATES;
    }


    // Traits
    // =========================================================================

    use SoftDeleteTrait;
}
