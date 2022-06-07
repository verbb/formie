<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;
use craft\records\Field;

use yii\db\ActiveQueryInterface;

class Payment extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%formie_payments}}';
    }


    // Public Methods
    // =========================================================================

    /**
     * @return ActiveQueryInterface
     */
    public function getIntegration(): ActiveQueryInterface
    {
        return $this->hasOne(Integration::class, ['id' => 'integrationId']);
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getSubmission(): ActiveQueryInterface
    {
        return $this->hasOne(Submission::class, ['id' => 'submissionId']);
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getField(): ActiveQueryInterface
    {
        return $this->hasOne(Field::class, ['id' => 'fieldId']);
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getSubscription(): ActiveQueryInterface
    {
        return $this->hasOne(Subscription::class, ['id' => 'subscriptionId']);
    }
}
