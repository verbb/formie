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
 * @property boolean $requireUser
 * @property string $availability
 * @property DateTime $availabilityFrom
 * @property DateTime $availabilityTo
 * @property int $availabilitySubmissions
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
    // Private Properties
    // =========================================================================

    /**
     * @var string
     */
    private string $_oldHandle;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function tableName(): string
    {
        return '{{%formie_forms}}';
    }

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
