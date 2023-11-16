<?php
namespace verbb\formie\records;

use craft\base\Element;
use craft\base\Field;
use craft\db\ActiveRecord;

use yii\db\ActiveQueryInterface;

class NestedFieldRow extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%formie_nestedfieldrows}}';
    }


    // Public Methods
    // =========================================================================

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    public function getOwner(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'ownerId']);
    }

    public function getField(): ActiveQueryInterface
    {
        return $this->hasOne(Field::class, ['id' => 'fieldId']);
    }
}
