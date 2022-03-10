<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;

/**
 * Class Row
 *
 * @property int $id
 * @property int $fieldLayoutId
 * @property int $fieldLayoutFieldId
 * @property int $row
 *
 * @package Formie
 */
class Row extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function tableName(): string
    {
        return '{{%formie_rows}}';
    }
}
