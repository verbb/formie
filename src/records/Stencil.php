<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;

/**
 * Class Stencil
 *
 * @property int $id
 * @property string $name
 * @property string $handle
 * @property string $data
 * @property int $templateId
 * @property int $defaultStatusId
 * @property bool $dateDeleted
 *
 * @package Formie
 */
class Stencil extends ActiveRecord
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
        return '{{%formie_stencils}}';
    }
}
