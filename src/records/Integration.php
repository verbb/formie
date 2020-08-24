<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;
use craft\records\Element;

use DateTime;
use yii\db\ActiveQueryInterface;

/**
 * Class Integration
 *
 * @property int $id
 *
 * @package Formie
 */
class Integration extends ActiveRecord
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
        return '{{%formie_integrations}}';
    }
}
