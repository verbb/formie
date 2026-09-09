<?php
namespace verbb\formie\migrations;

use verbb\formie\content\SubmissionContentNormalizer;
use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m260709_000000_fix_double_encoded_submission_content extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_SUBMISSIONS) || !$this->db->columnExists(Table::FORMIE_SUBMISSIONS, 'content')) {
            return true;
        }

        // Portable scan: avoid MySQL-only JSON_TYPE so PostgreSQL upgrades succeed.
        // Double-encoded rows decode once to a JSON string; rewrite as a native object.
        $submissionRows = (new Query())
            ->select(['id', 'content'])
            ->from(Table::FORMIE_SUBMISSIONS)
            ->where(['not', ['content' => null]])
            ->batch(200);

        foreach ($submissionRows as $rows) {
            foreach ($rows as $row) {
                $submissionId = (int)($row['id'] ?? 0);
                $raw = $row['content'] ?? null;

                if (!$submissionId || !$this->_isDoubleEncodedContent($raw)) {
                    continue;
                }

                $decoded = SubmissionContentNormalizer::decodeStoredPayload($raw);

                if (!$decoded) {
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


    // Private Methods
    // =========================================================================

    private function _isDoubleEncodedContent(mixed $raw): bool
    {
        if (is_array($raw)) {
            return false;
        }

        if (!is_string($raw) || trim($raw) === '') {
            return false;
        }

        try {
            $once = Json::decode($raw);
        } catch (\Throwable) {
            return false;
        }

        // A JSON string value (e.g. "\"{...}\"") is the double-encoded shape this migration repairs.
        return is_string($once);
    }
}
