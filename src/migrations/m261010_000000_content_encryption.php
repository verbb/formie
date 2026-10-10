<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;

class m261010_000000_content_encryption extends Migration
{
    // Public Methods
    // =========================================================================

    // Existing installations stay on v1 writes until the explicit activation command runs.
    public function safeUp(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_ENCRYPTION_SETTINGS)) {
            $this->createTable(Table::FORMIE_ENCRYPTION_SETTINGS, [
                'id' => $this->primaryKey(),
                'contentVersion' => $this->smallInteger()->notNull()->defaultValue(1),
                'expectedKeyId' => $this->string(18),
                'reencryptCursor' => $this->integer(),
                'reencryptTargetKeyId' => $this->string(18),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
            ]);

            $this->insert(Table::FORMIE_ENCRYPTION_SETTINGS, [
                'id' => 1,
                'contentVersion' => 1,
                'expectedKeyId' => null,
            ]);
        }

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists(Table::FORMIE_ENCRYPTION_SETTINGS);

        return true;
    }
}
