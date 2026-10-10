<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\IntegrationSecrets;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m261010_010000_plain_integration_settings extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->_migrateJsonColumn(Table::FORMIE_INTEGRATIONS, 'settings');
        $this->_migrateNestedJsonColumn(Table::FORMIE_FORMS, 'settings', ['integrations']);
        $this->_migrateNestedJsonColumn(Table::FORMIE_STENCILS, 'data', ['settings', 'integrations']);

        if (Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $projectConfig = Craft::$app->getProjectConfig();

            foreach (['formie.integrations', 'formie.stencils'] as $path) {
                $value = $projectConfig->get($path, true);
                $revealed = is_array($value) ? IntegrationSecrets::reveal($value) : $value;

                if ($revealed !== $value) {
                    $projectConfig->set($path, $revealed, 'Store Formie integration settings without beta encryption');
                }
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261010_010000_plain_integration_settings cannot restore beta-encrypted integration settings.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _migrateJsonColumn(string $table, string $column): void
    {
        foreach ((new Query())->select(['id', $column])->from($table)->each() as $row) {
            $value = Json::decodeIfJson($row[$column]);
            $revealed = is_array($value) ? IntegrationSecrets::reveal($value) : $value;

            if ($revealed !== $value) {
                $this->update($table, [$column => Json::encode($revealed)], ['id' => $row['id']]);
            }
        }
    }

    private function _migrateNestedJsonColumn(string $table, string $column, array $path): void
    {
        foreach ((new Query())->select(['id', $column])->from($table)->each() as $row) {
            $value = Json::decodeIfJson($row[$column]);

            if (!is_array($value)) {
                continue;
            }

            $cursor = & $value;

            foreach ($path as $segment) {
                if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                    continue 2;
                }

                $cursor = & $cursor[$segment];
            }

            $revealed = IntegrationSecrets::reveal($cursor);

            if ($revealed !== $cursor) {
                $cursor = $revealed;
                $this->update($table, [$column => Json::encode($value)], ['id' => $row['id']]);
            }
        }
    }
}
