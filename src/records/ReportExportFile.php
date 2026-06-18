<?php
namespace verbb\formie\records;

use verbb\formie\helpers\Table;

use craft\db\ActiveRecord;

class ReportExportFile extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    public static function tableName(): string
    {
        return Table::FORMIE_REPORT_EXPORTS;
    }
}
