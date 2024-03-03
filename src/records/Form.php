<?php
namespace verbb\formie\records;

use verbb\formie\helpers\Table;

use craft\db\ActiveRecord;
use craft\records\Element;

use DateTime;

use yii\db\ActiveQueryInterface;

class Form extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    public static function tableName(): string
    {
        return Table::FORMIE_FORMS;
    }


    // Properties
    // =========================================================================

    private string $_oldHandle;


    // Public Methods
    // =========================================================================

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    public function afterFind(): void
    {
        parent::afterFind();
        $this->_oldHandle = $this->handle;
    }

    public function getOldHandle(): string
    {
        return $this->_oldHandle;
    }
}
