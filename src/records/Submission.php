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
 * @property boolean $siteId
 * @property int $statusId
 * @property int $userId
 * @property boolean $isIncomplete
 * @property boolean $isSpam
 * @property boolean $spamReason
 * @property Element $element
 *
 * @package Formie
 */
class Submission extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function tableName(): string
    {
        return '{{%formie_submissions}}';
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
