<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;

class m260618_000000_report_export_download_security extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_REPORT_EXPORTS)) {
            return true;
        }

        if ($this->db->columnExists(Table::FORMIE_REPORT_EXPORTS, 'downloadToken')) {
            $this->dropIndexIfExists(Table::FORMIE_REPORT_EXPORTS, ['downloadToken'], true);

            if (!$this->db->columnExists(Table::FORMIE_REPORT_EXPORTS, 'downloadUrl')) {
                $this->addColumn(Table::FORMIE_REPORT_EXPORTS, 'downloadUrl', $this->text()->after('fileSize'));
            }

            if (!$this->db->columnExists(Table::FORMIE_REPORT_EXPORTS, 'dateDownloaded')) {
                $this->addColumn(Table::FORMIE_REPORT_EXPORTS, 'dateDownloaded', $this->dateTime()->after('dateExpires'));
            }

            $this->renameColumn(Table::FORMIE_REPORT_EXPORTS, 'downloadToken', 'downloadTokenHash');

            $this->_hashPlaintextDownloadTokens();
        } elseif ($this->db->columnExists(Table::FORMIE_REPORT_EXPORTS, 'downloadTokenHash')) {
            // Resume token hashing if a previous run renamed the column but did not finish.
            $this->_hashPlaintextDownloadTokens();
        }

        if ($this->db->columnExists(Table::FORMIE_REPORT_EXPORTS, 'downloadTokenHash')) {
            $this->alterColumn(
                Table::FORMIE_REPORT_EXPORTS,
                'downloadTokenHash',
                $this->char(64)->null(),
            );
        }

        return true;
    }

    public function safeDown(): bool
    {
        return true;
    }


    // Private Methods
    // =========================================================================

    private function _hashPlaintextDownloadTokens(): void
    {
        $rows = (new Query())
            ->select(['id', 'downloadTokenHash'])
            ->from(Table::FORMIE_REPORT_EXPORTS)
            ->where(['not', ['downloadTokenHash' => null]])
            ->andWhere(['<>', 'downloadTokenHash', ''])
            ->all($this->db);

        foreach ($rows as $row) {
            $token = (string)$row['downloadTokenHash'];

            // Plaintext UUID tokens are 36 chars; SHA-256 hex is 64.
            if (strlen($token) === 64 && ctype_xdigit($token)) {
                continue;
            }

            $this->update(
                Table::FORMIE_REPORT_EXPORTS,
                ['downloadTokenHash' => hash('sha256', $token)],
                ['id' => (int)$row['id']],
            );
        }
    }
}
