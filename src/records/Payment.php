<?php
namespace verbb\formie\records;

use verbb\formie\helpers\Table;

use craft\db\ActiveRecord;
use craft\records\Field;

use yii\db\ActiveQueryInterface;

class Payment extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    public static function tableName(): string
    {
        return Table::FORMIE_PAYMENTS;
    }


    // Public Methods
    // =========================================================================

    public function getIntegration(): ActiveQueryInterface
    {
        return $this->hasOne(Integration::class, ['id' => 'integrationId']);
    }

    public function getSubmission(): ActiveQueryInterface
    {
        return $this->hasOne(Submission::class, ['id' => 'submissionId']);
    }

    public function getField(): ActiveQueryInterface
    {
        return $this->hasOne(Field::class, ['id' => 'fieldId']);
    }

    public function getSubscription(): ActiveQueryInterface
    {
        return $this->hasOne(Subscription::class, ['id' => 'subscriptionId']);
    }
}
