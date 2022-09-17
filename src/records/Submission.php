<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;
use craft\records\Element;

use yii\db\ActiveQueryInterface;

/**
 * Class Submission
 *
 * @property int $id
 * @property string $title
 * @property int $formId
 * @property int $statusId
 * @property int $userId
 * @property boolean $isIncomplete
 * @property boolean $isSpam
 * @property string $spamReason
 * @property string $spamClass
 * @property Element $element
 *
 * @package Formie
 */
class Submission extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function tableName(): string
    {
        return '{{%formie_submissions}}';
    }


    // Public Methods
    // =========================================================================

    /**
     * @return ActiveQueryInterface
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
