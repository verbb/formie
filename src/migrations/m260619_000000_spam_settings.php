<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\helpers\MigrationHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\services\Integrations;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Db;

use DateTime;

class m260619_000000_spam_settings extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_SPAM_SETTINGS)) {
            $this->createTable(Table::FORMIE_SPAM_SETTINGS, [
                'id' => $this->primaryKey(),
                'scope' => $this->string(16)->notNull()->defaultValue(Integrations::SCOPE_PROJECT),
                'saveSpam' => $this->boolean()->notNull()->defaultValue(true),
                'spamLimit' => $this->integer()->notNull()->defaultValue(500),
                'spamEmailNotifications' => $this->boolean()->notNull()->defaultValue(false),
                'spamBehaviour' => $this->string()->notNull()->defaultValue('showSuccess'),
                'spamBehaviourMessage' => $this->text(),
                'spamKeywords' => $this->mediumText(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        $settings = Formie::$plugin->getSettings();

        if (!(new Query())->from(Table::FORMIE_SPAM_SETTINGS)->exists()) {
            $now = Db::prepareDateForDb(new DateTime());

            $this->insert(Table::FORMIE_SPAM_SETTINGS, [
                'scope' => Integrations::SCOPE_PROJECT,
                'saveSpam' => (bool)$settings->saveSpam,
                'spamLimit' => (int)$settings->spamLimit,
                'spamEmailNotifications' => (bool)$settings->spamEmailNotifications,
                'spamBehaviour' => (string)$settings->spamBehaviour,
                'spamBehaviourMessage' => (string)$settings->spamBehaviourMessage,
                'spamKeywords' => (string)$settings->spamKeywords,
                'dateCreated' => $now,
                'dateUpdated' => $now,
                'uid' => StringHelper::UUID(),
            ]);
        }

        $settingsArray = $settings->toArray();
        $settingsArray = Formie::$plugin->getSpamProtection()->stripFromPluginSettingsArray($settingsArray);

        MigrationHelper::savePluginSettingsIfAllowed(Formie::$plugin, $settingsArray);

        return true;
    }

    public function safeDown(): bool
    {
        if ($this->db->tableExists(Table::FORMIE_SPAM_SETTINGS)) {
            $this->dropTable(Table::FORMIE_SPAM_SETTINGS);
        }

        return true;
    }
}
