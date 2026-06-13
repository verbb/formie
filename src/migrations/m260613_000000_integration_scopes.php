<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;
use verbb\formie\services\Integrations;

use craft\db\Migration;

class m260613_000000_integration_scopes extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->columnExists(Table::FORMIE_INTEGRATIONS, 'scope')) {
            $this->addColumn(
                Table::FORMIE_INTEGRATIONS,
                'scope',
                $this->string(16)->notNull()->defaultValue(Integrations::SCOPE_PROJECT)->after('handle'),
            );
        }

        $this->update(Table::FORMIE_INTEGRATIONS, ['scope' => Integrations::SCOPE_PROJECT], ['scope' => null]);

        return true;
    }

    public function safeDown(): bool
    {
        if ($this->db->columnExists(Table::FORMIE_INTEGRATIONS, 'scope')) {
            $this->dropColumn(Table::FORMIE_INTEGRATIONS, 'scope');
        }

        return true;
    }
}
