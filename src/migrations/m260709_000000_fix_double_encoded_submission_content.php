<?php
namespace verbb\formie\migrations;

use verbb\formie\content\SubmissionContentNormalizer;
use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;

class m260709_000000_fix_double_encoded_submission_content extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_SUBMISSIONS) || !$this->db->columnExists(Table::FORMIE_SUBMISSIONS, 'content')) {
            return true;
        }

        $submissionRows = (new Query())
            ->select(['id', 'content'])
            ->from(Table::FORMIE_SUBMISSIONS)
            ->where('JSON_TYPE([[content]]) = :jsonType', [':jsonType' => 'STRING'])
            ->batch(200);

        foreach ($submissionRows as $rows) {
            foreach ($rows as $row) {
                $submissionId = (int)($row['id'] ?? 0);
                $decoded = SubmissionContentNormalizer::decodeStoredPayload($row['content'] ?? null);

                if (!$submissionId || !$decoded) {
                    continue;
                }

                // Rewrite as a native JSON object so future reads/saves round-trip correctly.
                $this->update(
                    Table::FORMIE_SUBMISSIONS,
                    ['content' => $decoded],
                    ['id' => $submissionId],
                );
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260709_000000_fix_double_encoded_submission_content cannot be reverted.\n";

        return false;
    }
}
