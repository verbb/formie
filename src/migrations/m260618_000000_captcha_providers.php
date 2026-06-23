<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\helpers\MigrationHelper;
use verbb\formie\helpers\Table;
use verbb\formie\services\Integrations;

use Craft;
use craft\db\Migration;

class m260618_000000_captcha_providers extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_CAPTCHA_PROVIDERS)) {
            $this->createTable(Table::FORMIE_CAPTCHA_PROVIDERS, [
                'id' => $this->primaryKey(),
                'handle' => $this->string(64)->notNull(),
                'type' => $this->string()->notNull(),
                'scope' => $this->string(16)->notNull()->defaultValue(Integrations::SCOPE_PROJECT),
                'enabled' => $this->string()->notNull()->defaultValue('false'),
                'saveSpam' => $this->boolean(),
                'settings' => $this->mediumText(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        $this->createIndexIfMissing(Table::FORMIE_CAPTCHA_PROVIDERS, ['handle'], true);

        $settings = Formie::$plugin->getSettings();
        $legacyCaptchas = is_array($settings->captchas) ? $settings->captchas : [];

        Formie::$plugin->getCaptchaProviders()->seedRegistryFromLegacySettings($legacyCaptchas);

        if ($legacyCaptchas !== []) {
            $settings->captchas = [];
            MigrationHelper::savePluginSettingsIfAllowed(Formie::$plugin, $settings->toArray());
        }

        return true;
    }

    public function safeDown(): bool
    {
        if ($this->db->tableExists(Table::FORMIE_CAPTCHA_PROVIDERS)) {
            $this->dropTable(Table::FORMIE_CAPTCHA_PROVIDERS);
        }

        return true;
    }
}
