<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;
use craft\records\FieldLayout;
use yii\db\ActiveQueryInterface;

class FormTemplate extends ActiveRecord
{
    // Traits
    // =========================================================================

    use SoftDeleteTrait;


    // Static Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%formie_formtemplates}}';
    }

    public function getFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'fieldLayoutId']);
    }
}
