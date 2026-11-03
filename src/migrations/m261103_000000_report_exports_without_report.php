<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m261103_000000_report_exports_without_report extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Submission index exports queue without a saved report. Existing rows and
        // the foreign key to saved reports are unaffected.
        $this->alterColumn(Table::FORMIE_REPORT_EXPORTS, 'reportId', $this->integer()->null());

        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }
}
