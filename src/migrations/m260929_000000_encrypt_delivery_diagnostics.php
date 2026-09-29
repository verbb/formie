<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m260929_000000_encrypt_delivery_diagnostics extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $table = '{{%formie_delivery_diagnostics}}';
        if (!$this->db->tableExists($table)) {
            return true;
        }

        $this->alterColumn($table, 'data', $this->mediumText()->notNull());

        foreach ((new Query())->select(['id', 'data'])->from($table)->each() as $row) {
            $data = Json::decodeIfJson($row['data']);
            if (!is_array($data)) {
                continue;
            }

            $plain = Json::encode($data);
            $cipher = Craft::$app->getSecurity()->encryptByKey($plain, Formie::$plugin->getSettings()->getSecurityKey());
            $this->update($table, ['data' => base64_encode($cipher)], ['id' => $row['id']], [], false);
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260929_000000_encrypt_delivery_diagnostics cannot be reverted.\n";

        return false;
    }
}
