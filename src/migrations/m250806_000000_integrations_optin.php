<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m250806_000000_integrations_optin extends Migration
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
                    foreach ($integration as $integrationProp => $mapKey) {
                        if (in_array($integrationProp, ['optInField']) && is_string($mapKey)) {
                            // Rename any old array-like syntax `group[nested][field]` with dot-notation `group.nested.field`
                            if (str_contains($mapKey, '[')) {
                                $hasChanged = true;
                                $integrations[$integrationKey][$integrationProp] = $mapKey = str_replace(['[', ']'], ['.', ''], $mapKey);
                            }

                            // Rename `{*}` to `{field:*}` - but watch out for `{submission:*}`
                            if (str_starts_with($mapKey, '{') && !str_starts_with($mapKey, '{submission:') && !str_starts_with($mapKey, '{field:')) {
                                $hasChanged = true;
                                $integrations[$integrationKey][$integrationProp] = $mapKey = str_replace('{', '{field:', $mapKey);
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
        echo "m250806_000000_integrations_optin cannot be reverted.\n";

        return false;
    }
}
