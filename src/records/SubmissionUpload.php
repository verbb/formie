<?php
namespace verbb\formie\records;

use verbb\formie\helpers\Table;

use craft\db\ActiveRecord;

/** Lifecycle and recovery state for a scoped submission asset. */
class SubmissionUpload extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    public static function tableName(): string
    {
        return Table::FORMIE_PENDING_UPLOADS;
    }
}
