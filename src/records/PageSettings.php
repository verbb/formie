<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;

/**
 * Class PageSettings
 *
 * @property int $id
 * @property int $fieldLayoutId
 * @property int $fieldLayoutTabId
 * @property string $settings
 *
 * @package Formie
 */
class PageSettings extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function tableName(): string
    {
        return '{{%formie_pagesettings}}';
    }
}
