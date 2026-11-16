<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m260913_000001_integration_field_references extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->_normalizeFormSettingsForHydration();

        foreach ((new Query())->select(['id', 'settings'])->from(Table::FORMIE_FORMS)->each() as $row) {
            $settings = Json::decode($row['settings']) ?: [];

            if (empty($settings['integrations'])) {
                continue;
            }

            $form = Form::find()->id($row['id'])->status(null)->one();

            if (!$form) {
                continue;
            }

            $original = Json::encode($settings['integrations']);
            $updated = preg_replace_callback('/\{field:([^}:;|]+)([^}]*)\}/', static function(array $match) use ($form): string {
                $field = $form->getFieldByHandle($match[1]);

                return $field && $field->reference
                    ? '{field:' . $field->reference . $match[2] . '}'
                    : $match[0];
            }, $original);

            if ($updated !== $original) {
                $settings['integrations'] = Json::decode($updated);
                $this->update(Table::FORMIE_FORMS, ['settings' => Json::encode($settings)], ['id' => $row['id']], [], false);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260913_000001_integration_field_references cannot be reverted.\n";

        return false;
    }

    // Private Methods
    // =========================================================================

    /**
     * Remove beta-only settings that the following form query can no longer hydrate.
     *
     * The dedicated integration-policy migration processes every persisted copy later.
     * This preflight only unblocks canonical form loading for this migration.
     */
    private function _normalizeFormSettingsForHydration(): void
    {
        if (!$this->db->tableExists(Table::FORMIE_FORMS) || !$this->db->columnExists(Table::FORMIE_FORMS, 'settings')) {
            return;
        }

        foreach ((new Query())->select(['id', 'settings'])->from(Table::FORMIE_FORMS)->each() as $row) {
            $data = Json::decodeIfJson($row['settings'] ?? null);

            if (!is_array($data)) {
                continue;
            }

            $normalized = m260929_000000_form_integration_policy::normalize($data);

            if ($normalized !== $data) {
                $this->update(Table::FORMIE_FORMS, ['settings' => Json::encode($normalized)], ['id' => $row['id']], [], false);
            }
        }
    }
}
