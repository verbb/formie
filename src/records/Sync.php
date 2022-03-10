<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;

/**
 * Class Sync
 *
 * @property int $id
 *
 * @package Formie
 */
class Sync extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function tableName(): string
    {
        return '{{%formie_syncs}}';
    }
}
