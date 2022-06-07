<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;

use yii\db\ActiveQueryInterface;

class Plan extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%formie_payments_plans}}';
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
}
