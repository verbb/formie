<?php

namespace verbb\formie\migrations;

use craft\db\Migration;
use verbb\formie\elements\Form;

class m230716_000000_add_status_to_form extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%formie_forms}}', 'formStatus')) {
            $this->addColumn('{{%formie_forms}}', 'formStatus', $this->string());
            $this->update('{{%formie_forms}}', ['formStatus' => Form::STATUS_ACTIVE]);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        if ($this->db->columnExists('{{%formie_forms}}', 'formStatus')) {
            $this->dropColumn('{{%formie_forms}}', 'formStatus');
        }

        return true;
    }
}
