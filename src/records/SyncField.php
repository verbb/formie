<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;

/**
 * Class SyncField
 *
 * @property int $id
 * @property int $syncId
 * @property int $fieldId
 *
 * @package Formie
 */
class SyncField extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function tableName(): string
    {
        return '{{%formie_syncfields}}';
    }
}
