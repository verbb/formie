<?php
namespace verbb\formie\migrations;

use verbb\formie\fields\Date;
use verbb\formie\fields\values\DateFieldValue;
use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m260223_000001_backfill_date_parts extends Migration
{
    // Constants
    // =========================================================================

    private const DATE_PART_KEYS = ['year', 'month', 'day', 'hour', 'minute', 'second', 'ampm'];


    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $fieldTable = $this->db->tableExists(Table::FORMIE_FORM_FIELDS) ? Table::FORMIE_FORM_FIELDS : Table::FORMIE_FIELDS;

        if (!$this->db->tableExists(Table::FORMIE_SUBMISSIONS) || !$this->db->tableExists($fieldTable)) {
            return true;
        }

        if ($fieldTable === Table::FORMIE_FORM_FIELDS) {
            $dateFieldUids = (new Query())
                ->select(['ff.uid'])
                ->from(['ff' => Table::FORMIE_FORM_FIELDS])
                ->innerJoin(['f' => Table::FORMIE_FIELDS], '[[f.id]] = [[ff.fieldId]]')
                ->where(['f.type' => Date::class])
                ->column();
        } else {
            $dateFieldUids = (new Query())
                ->select(['uid'])
                ->from(Table::FORMIE_FIELDS)
                ->where(['type' => Date::class])
                ->column();
        }

        if (empty($dateFieldUids)) {
            return true;
        }

        $submissionRows = (new Query())
            ->select(['id', 'content'])
            ->from(Table::FORMIE_SUBMISSIONS)
            ->batch(200);

        foreach ($submissionRows as $rows) {
            foreach ($rows as $row) {
                $submissionId = (int)($row['id'] ?? 0);
                $content = $this->_normalizeContentPayload($row['content'] ?? null);

                if (!$submissionId || !is_array($content)) {
                    continue;
                }

                $changed = false;

                foreach ($dateFieldUids as $fieldUid) {
                    if (!array_key_exists($fieldUid, $content)) {
                        continue;
                    }

                    $fieldValue = $content[$fieldUid];

                    if ($this->_isCanonicalDateParts($fieldValue)) {
                        continue;
                    }

                    $parts = DateFieldValue::parseParts($fieldValue);

                    if (empty($parts)) {
                        continue;
                    }

                    $content[$fieldUid] = $parts;
                    $changed = true;
                }

                if (!$changed) {
                    continue;
                }

                $this->update(
                    Table::FORMIE_SUBMISSIONS,
                    ['content' => Json::encode($content)],
                    ['id' => $submissionId]
                );
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260223_000001_backfill_date_parts cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _normalizeContentPayload(mixed $content): mixed
    {
        if (is_array($content)) {
            return $content;
        }

        if (!is_string($content) || trim($content) === '') {
            return null;
        }

        try {
            $decoded = Json::decode($content, true);
        } catch (\Throwable) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    private function _isCanonicalDateParts(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        return !empty(array_intersect(array_keys($value), self::DATE_PART_KEYS));
    }
}
