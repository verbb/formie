<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

/**
 * Hot payment lookups by submissionId (poll/status paths) need an index;
 * Install.php had integration/field/reference indexes but not submissionId.
 */
class m260909_000000_payments_submission_id_index extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_PAYMENTS)) {
            return true;
        }

        $this->createIndexIfMissing(Table::FORMIE_PAYMENTS, 'submissionId', false);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260909_000000_payments_submission_id_index cannot be reverted.\n";

        return false;
    }
}
