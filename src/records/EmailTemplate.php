<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;
use craft\records\FieldLayout;
use yii\db\ActiveQueryInterface;

/**
 * Class EmailTemplate
 *
 * @property int $id
 * @property string $name
 * @property string $handle
 * @property string $template
 * @property int $sortOrder
 * @property bool $dateDeleted
 *
 * @package Formie
 */
class EmailTemplate extends ActiveRecord
{
    // Traits
    // =========================================================================

    use SoftDeleteTrait;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function tableName(): string
    {
        return '{{%formie_emailtemplates}}';
    }
}
