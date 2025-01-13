<?php
namespace verbb\formie\migrations;

use verbb\formie\fields\Email;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m250114_000000_email_blocked_domains extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $fields = (new Query())
            ->select(['*'])
            ->from([Table::FORMIE_FIELDS])
            ->where(['type' => Email::class])
            ->all();

        foreach ($fields as $field) {
            $settings = Json::decode($field['settings']);
            $blockedDomains = $settings['blockedDomains'] ?? [];

            // // As `formula` is rich text, it's far easier to parse as a JSON string, so convert to JSON, then back again.
            if (is_array($blockedDomains) && $blockedDomains) {
                foreach ($blockedDomains as $key => $blockedDomain) {
                    // All options are the same, but they might not all exist
                    $value = array_values(array_filter([
                        $blockedDomain['domain'] ?? null,
                        $blockedDomain['label'] ?? null,
                        $blockedDomain['value'] ?? null,
                    ]))[0] ?? '';

                    $blockedDomains[$key]['domain'] = $value;
                    $blockedDomains[$key]['label'] = $value;
                    $blockedDomains[$key]['value'] = $value;
                }

                $settings['blockedDomains'] = $blockedDomains;

                $this->update(Table::FORMIE_FIELDS, [
                    'settings' => Json::encode($settings),
                ], ['id' => $field['id']], [], false);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m250114_000000_email_blocked_domains cannot be reverted.\n";

        return false;
    }
}
