<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\helpers\RetiredCaptchas;
use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m261024_000000_retired_captchas extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $providers = Formie::$plugin->getCaptchaProviders();
        $providers->resetCache();

        // Read bindings before deleting providers so stored types can distinguish
        // retired built-ins from third-party providers with the same handles.
        foreach ([Table::FORMIE_FORMS => 'settings', Table::FORMIE_STENCILS => 'data'] as $table => $column) {
            foreach ((new Query())->select(['id', $column])->from($table)->each(100, $this->db) as $row) {
                $data = $row[$column];

                // Older stencils may have been JSON-encoded twice.
                for ($layer = 0; $layer < 2 && is_string($data); $layer++) {
                    $data = Json::decodeIfJson($data);
                }

                if (!is_array($data)) {
                    continue;
                }

                $clean = $data;

                if ($column === 'settings') {
                    $clean = RetiredCaptchas::removeBindings($data);
                } elseif (isset($data['settings']) && is_array($data['settings'])) {
                    $clean['settings'] = RetiredCaptchas::removeBindings($data['settings']);
                }

                if ($clean !== $data) {
                    $this->update($table, [$column => Json::encode($clean)], ['id' => $row['id']], updateTimestamp: false);
                }
            }
        }

        foreach ((new Query())->select(['id', 'handle', 'type'])->from(Table::FORMIE_CAPTCHA_PROVIDERS)->all($this->db) as $row) {
            if (RetiredCaptchas::isRetired($row['handle'], $row['type'])) {
                $this->delete(Table::FORMIE_CAPTCHA_PROVIDERS, ['id' => $row['id']]);
            }
        }

        $providers->resetCache();
        Formie::$plugin->getIntegrations()->resetCaptchaCaches();
        Formie::$plugin->getForms()->invalidateFormCaches();

        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }
}
