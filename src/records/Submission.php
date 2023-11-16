<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;
use craft\records\Element;

use yii\db\ActiveQueryInterface;

class Submission extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%formie_submissions}}';
    }


    // Public Methods
    // =========================================================================

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
