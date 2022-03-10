<?php
namespace verbb\formie\records;

use craft\base\Element;
use craft\base\Field;
use craft\db\ActiveRecord;

use yii\db\ActiveQueryInterface;

/**
 * Class NestedFieldRow
 *
 * @property int $id
 * @property int $fieldId
 * @property int $ownerId
 * @property int $sortOrder
 * @property bool $dateDeleted
 *
 * @package Formie
 */
class NestedFieldRow extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%formie_nestedfieldrows}}';
    }


    // Public Methods
    // =========================================================================

    /**
     * Returns the nested field row’s element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    /**
     * Returns the nested field row’s owner.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getOwner(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'ownerId']);
    }

    /**
     * Returns the nested field row’s field.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getField(): ActiveQueryInterface
    {
        return $this->hasOne(Field::class, ['id' => 'fieldId']);
    }
}
