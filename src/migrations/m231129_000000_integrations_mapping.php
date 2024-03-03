<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m231129_000000_integrations_mapping extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $forms = (new Query())
            ->select(['*'])
            ->from([Table::FORMIE_FORMS])
            ->all();

        foreach ($forms as $form) {
            $settings = Json::decode($form['settings']);
            $integrations = $settings['integrations'] ?? [];
            $hasChanged = false;

            if (is_array($integrations)) {
                foreach ($integrations as $integrationKey => $integration) {
                    foreach ($integration as $integrationProp => $integrationValue) {
                        // Field mapping is stored in different settings, so check them all
                        if (str_contains($integrationProp, 'Mapping') && is_array($integrationValue)) {
                            foreach ($integrationValue as $handle => $mapKey) {
                                if (is_string($mapKey)) {
                                    // Rename any old array-like syntax `group[nested][field]` with dot-notation `group.nested.field`
                                    if (str_contains($mapKey, '[')) {
                                        $hasChanged = true;
                                        $integrations[$integrationKey][$integrationProp][$handle] = $mapKey = str_replace(['[', ']'], ['.', ''], $mapKey);
                                    }

                                    // Rename `{*}` to `{field:*}` - but watch out for `{submission:*}`
                                    if (str_starts_with($mapKey, '{') && !str_starts_with($mapKey, '{submission:') && !str_starts_with($mapKey, '{field:')) {
                                        $hasChanged = true;
                                        $integrations[$integrationKey][$integrationProp][$handle] = $mapKey = str_replace('{', '{field:', $mapKey);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($hasChanged) {
                $settings['integrations'] = $integrations;

                $this->update(Table::FORMIE_FORMS, [
                    'settings' => Json::encode($settings),
                ], ['id' => $form['id']], [], false);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m231129_000000_integrations_mapping cannot be reverted.\n";

        return false;
    }
}
