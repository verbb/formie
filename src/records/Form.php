<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;
use craft\records\Element;

use DateTime;
use yii\db\ActiveQueryInterface;

/**
 * Class Form
 *
 * @property int $id
 * @property string $handle
 * @property string $fieldContentTable
 * @property string $settings
 * @property int $templateId
 * @property int $submitActionEntryId
 * @property int $submitActionEntrySiteId
 * @property int $defaultStatusId
 * @property string $dataRetention
 * @property int $dataRetentionValue
 * @property string $userDeletedAction
 * @property string $fileUploadsAction
 * @property int $fieldLayoutId
 * @property int $groupId
 * @property Element $element
 *
 * @package Formie
 */
class Form extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function tableName(): string
    {
        return '{{%formie_forms}}';
    }


    // Properties
    // =========================================================================
    /**
     * @var string
     */
    private string $_oldHandle;


    // Public Methods
    // =========================================================================
    /**
     * @return ActiveQueryInterface
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    /**
     * @inheritDoc
     */
    public function afterFind(): void
    {
        parent::afterFind();
        $this->_oldHandle = $this->handle;
    }

    /**
     * Returns the old form handle.
     *
     * @return string
     */
    public function getOldHandle(): string
    {
        return $this->_oldHandle;
    }
}
