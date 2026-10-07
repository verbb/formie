<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;
use verbb\formie\services\Stencils;

use craft\db\Migration;

class m260605_000000_stencil_scopes extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->columnExists(Table::FORMIE_STENCILS, 'scope')) {
            $this->addColumn(
                Table::FORMIE_STENCILS,
                'scope',
                $this->string(16)->notNull()->defaultValue(Stencils::SCOPE_PROJECT)->after('handle'),
            );
        }

        $this->update(Table::FORMIE_STENCILS, ['scope' => Stencils::SCOPE_PROJECT], ['scope' => null]);

        return true;
    }

    public function safeDown(): bool
    {
        if ($this->db->columnExists(Table::FORMIE_STENCILS, 'scope')) {
            $this->dropColumn(Table::FORMIE_STENCILS, 'scope');
        }

        return true;
    }
}
