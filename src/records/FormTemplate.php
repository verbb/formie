<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;
use craft\records\FieldLayout;
use yii\db\ActiveQueryInterface;

/**
 * Class FormTemplate
 *
 * @property int $id
 * @property string $name
 * @property string $handle
 * @property string $template
 * @property boolean $useCustomTemplates
 * @property boolean $outputCssLayout
 * @property boolean $outputCssTheme
 * @property boolean $outputJs
 * @property int $sortOrder
 * @property int $fieldLayoutId
 * @property bool $dateDeleted
 *
 * @package Formie
 */
class FormTemplate extends ActiveRecord
{
    // Traits
    // =========================================================================

    use SoftDeleteTrait;


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function tableName(): string
    {
        return '{{%formie_formtemplates}}';
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'fieldLayoutId']);
    }
}
