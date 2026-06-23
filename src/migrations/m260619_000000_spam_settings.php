<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\helpers\MigrationHelper;
use verbb\formie\helpers\Table;
use verbb\formie\services\Integrations;
use verbb\formie\services\SpamProtection;

use Craft;
use craft\db\Migration;

class m260619_000000_spam_settings extends Migration
{
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
        $legacy = [];

        foreach (SpamProtection::SETTING_KEYS as $key) {
            $legacy[$key] = $settings->$key;
        }

        Formie::$plugin->getSpamProtection()->seedFromLegacySettings($legacy);

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
