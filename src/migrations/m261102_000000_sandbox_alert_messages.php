<?php
namespace verbb\formie\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

class m261102_000000_sandbox_alert_messages extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Craft's Twig sandbox doesn't allow these values, and saved messages don't pick up new defaults.
        // Only whole output tags are rewritten, so other custom wording is left as it was.
        $replacements = [
            '/\{\{\s*emailResponse\s*\|\s*json_encode\s*\}\}/' => '{{ emailResponseJson }}',
            '/\{\{\s*integrationResponse\s*\|\s*json_encode\s*\}\}/' => '{{ integrationResponseJson }}',
            '/\{\{\s*integration\.name\s*\}\}/' => '{{ integrationName }}',
        ];

        $messages = (new Query())
            ->select(['id', 'subject', 'body'])
            ->from(Table::SYSTEMMESSAGES)
            ->where(['key' => ['formie_failed_notification', 'formie_failed_integration']])
            ->all($this->db);

        foreach ($messages as $message) {
            $subject = preg_replace(array_keys($replacements), array_values($replacements), $message['subject']);
            $body = preg_replace(array_keys($replacements), array_values($replacements), $message['body']);

            if ($subject !== $message['subject'] || $body !== $message['body']) {
                $this->update(Table::SYSTEMMESSAGES, ['subject' => $subject, 'body' => $body], ['id' => $message['id']]);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261102_000000_sandbox_alert_messages cannot be reverted.\n";

        return false;
    }
}
