<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m261014_000000_payment_field_foreign_keys extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        foreach ([Table::FORMIE_PAYMENTS, Table::FORMIE_SUBSCRIPTIONS] as $table) {
            $schema = $this->db->getTableSchema($table, true);

            // Upgraded installations can retain the old definition foreign key
            // alongside the occurrence key. Payment fields use occurrence IDs.
            foreach ($schema->foreignKeys as $name => $foreignKey) {
                if (isset($foreignKey['fieldId'])) {
                    $this->dropForeignKey($name, $table);
                }
            }

            $orphans = (new Query())
                ->select(['payment.id', 'payment.fieldId', 'payment.scope'])
                ->from(['payment' => $table])
                ->leftJoin(['field' => Table::FORMIE_FORM_FIELDS], '[[field.id]] = [[payment.fieldId]]')
                ->where(['and', ['not', ['payment.fieldId' => null]], ['field.id' => null]]);

            foreach ($orphans->each(100, $this->db) as $row) {
                $scope = Json::decodeIfJson($row['scope']) ?: [];
                $scope['legacyFieldId'] = $row['fieldId'];
                $this->update($table, ['fieldId' => null, 'scope' => Json::encode($scope)], ['id' => $row['id']]);
            }

            $this->addForeignKey(null, $table, ['fieldId'], Table::FORMIE_FORM_FIELDS, ['id'], 'SET NULL');
        }

        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }
}
