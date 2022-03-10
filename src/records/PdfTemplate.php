<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;

/**
 * Class PdfTemplate
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
class PdfTemplate extends ActiveRecord
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
        return '{{%formie_pdftemplates}}';
    }
}
