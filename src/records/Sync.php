<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;
use craft\records\Element;

use DateTime;
use yii\db\ActiveQueryInterface;

/**
 * Class Sync
 *
 * @property int $id
 *
 * @package Formie
 */
class Sync extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function tableName(): string
    {
        return '{{%formie_syncs}}';
    }
}
