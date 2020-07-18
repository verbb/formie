<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;
use craft\records\Element;

use DateTime;
use yii\db\ActiveQueryInterface;

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
    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function tableName(): string
    {
        return '{{%formie_syncfields}}';
    }
}
