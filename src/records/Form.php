<?php
namespace verbb\formie\records;

use verbb\formie\helpers\Table;
use verbb\formie\Formie;
use Craft;

use craft\db\ActiveRecord;
use craft\records\Element;

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

    public function transactions(): array
    {
        return [$this->getScenario() => self::OP_ALL];
    }

    public function afterSave($insert, $changedAttributes): void
    {
        if ($insert || array_key_exists('layoutId', $changedAttributes)) {
            Formie::$plugin->getUploadReferences()->refreshSchemas((int)$this->id);
        }

        parent::afterSave($insert, $changedAttributes);
    }

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
