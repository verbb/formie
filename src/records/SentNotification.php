<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;
use craft\records\Element;

use yii\db\ActiveQueryInterface;

/**
 * Class SentNotification
 *
 * @property int $id
 *
 * @package Formie
 */
class SentNotification extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function tableName(): string
    {
        return '{{%formie_sentnotifications}}';
    }


    // Public Methods
    // =========================================================================

    /**
     * @return ActiveQueryInterface
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
