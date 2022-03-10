<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;

/**
 * Class Status
 *
 * @property int $id
 * @property string $name
 * @property string $handle
 * @property string $color
 * @property string $description
 * @property int $sortOrder
 * @property bool $dateDeleted
 *
 * @package Formie
 */
class Status extends ActiveRecord
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
        return '{{%formie_statuses}}';
    }
}
