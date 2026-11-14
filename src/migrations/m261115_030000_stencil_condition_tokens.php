<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\conditions\ConditionMigration;
use verbb\formie\elements\Form;
use verbb\formie\helpers\Table;
use verbb\formie\models\StencilData;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\ProjectConfig;

use Throwable;

class m261115_030000_stencil_condition_tokens extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        foreach ((new Query())->select(['id', 'data'])->from(Table::FORMIE_STENCILS)->each(100, $this->db) as $row) {
            $data = $row['data'];

            // Older stencils may have been JSON-encoded twice.
            for ($layer = 0; $layer < 2 && is_string($data); $layer++) {
                $data = Json::decodeIfJson($data);
            }

            if (is_array($data) && ($migrated = $this->_migrate($data)) !== $data) {
                $this->update(Table::FORMIE_STENCILS, ['data' => Json::encode($migrated)], ['id' => $row['id']], updateTimestamp: false);
            }
        }

        $projectConfig = Craft::$app->getProjectConfig();

        // Config that has already reached this version carries the converted tokens and is applied as it is.
        if ($projectConfig->readOnly || version_compare($projectConfig->get('plugins.formie.schemaVersion', true) ?? '0', Formie::$plugin->schemaVersion, '>=')) {
            return true;
        }

        // Not the external config, which doesn't have what earlier migrations in this run have saved.
        foreach ((array)$projectConfig->get('formie.stencils') as $uid => $config) {
            // The whole entry is packed, not just its data.
            $config = ProjectConfig::unpackAssociativeArray((array)$config);

            if (is_array($config['data'] ?? null) && ($migrated = $this->_migrate($config['data'])) !== $config['data']) {
                $projectConfig->set('formie.stencils.' . $uid, ProjectConfig::packAssociativeArray(['data' => $migrated] + $config), 'Convert old condition tokens in Formie stencils');
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261115_030000_stencil_condition_tokens cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _migrate(array $data): array
    {
        $form = new Form();

        try {
            (new StencilData($data))->populateToForm($form);
        } catch (Throwable) {
            // A stencil that can't be read has no fields to check its tokens against, and shouldn't stop an upgrade.
            return $data;
        }

        // Stencil fields have no references of their own until a form is made from them, which turns these paths into references.
        return ConditionMigration::legacyFieldTokens($data, $form, false);
    }
}
