<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Connection;
use craft\db\Migration;
use craft\db\Query;

class m261104_000000_payment_status_constraint extends Migration
{
    // Static Methods
    // =========================================================================

    public static function dropStatusChecks(Connection $db): void
    {
        // Older installs created the status as an enum, which is a CHECK constraint on
        // PostgreSQL that outlives the column becoming a string. MySQL has no equivalent.
        if (!$db->getIsPgsql() || !$db->tableExists(Table::FORMIE_PAYMENTS)) {
            return;
        }

        $table = $db->quoteTableName($db->getSchema()->getRawTableName(Table::FORMIE_PAYMENTS));

        $names = (new Query())
            ->select(['con.conname'])
            ->from(['con' => 'pg_constraint'])
            ->innerJoin(['att' => 'pg_attribute'], '[[att.attrelid]] = [[con.conrelid]] AND [[att.attnum]] = ANY([[con.conkey]])')
            ->where(['con.contype' => 'c', 'att.attname' => 'status'])
            ->andWhere('[[con.conrelid]] = to_regclass(:table)', [':table' => $table])
            ->column($db);

        foreach ($names as $name) {
            $db->createCommand()->dropCheck($name, Table::FORMIE_PAYMENTS)->execute();
        }
    }


    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Upgraded databases with no payments, or only failed ones, passed the original
        // migrations with the constraint intact and now reject current payment statuses.
        self::dropStatusChecks($this->db);

        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }
}
