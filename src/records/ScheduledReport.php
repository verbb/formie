<?php
namespace verbb\formie\records;

use verbb\formie\helpers\Table;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;

class ScheduledReport extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    public static function tableName(): string
    {
        return Table::FORMIE_SCHEDULED_REPORTS;
    }


    // Traits
    // =========================================================================

    use SoftDeleteTrait;
}
