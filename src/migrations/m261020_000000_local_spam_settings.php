<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\SpamProtectionSettings;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;

class m261020_000000_local_spam_settings extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Preserve raw values, IDs, credentials and form bindings. Only their former scope changes.
        $this->update(Table::FORMIE_CAPTCHA_PROVIDERS, ['scope' => 'site'], updateTimestamp: false);
        $this->update(Table::FORMIE_SPAM_SETTINGS, ['scope' => 'site'], updateTimestamp: false);

        foreach ([Table::FORMIE_CAPTCHA_PROVIDERS, Table::FORMIE_SPAM_SETTINGS] as $table) {
            if ($this->db->getTableSchema($table, true)->columns['scope']->defaultValue !== 'site') {
                $this->alterColumn($table, 'scope', $this->string(16)->notNull()->defaultValue('site'));
            }
        }

        // Remove metadata from the superseded, unreleased ownership workflow after retaining runtime values.
        if ($this->db->tableExists('{{%formie_spam_management}}')) {
            $this->dropTable('{{%formie_spam_management}}');
        }
        SpamProtectionSettings::resetCaches();

        $legacyNames = ['formie-accesssettings', 'formie-settingsspamprotection', 'formie-settingsspam', 'formie-settingscaptchas'];

        foreach (['formie-managecaptchas', 'formie-managespamrules'] as $name) {
            $this->db->createCommand()->upsert(Table::USERPERMISSIONS, ['name' => $name], false)->execute();
            $permissionId = (new Query())->select('id')->from(Table::USERPERMISSIONS)->where(['name' => $name])->scalar();

            foreach ([Table::USERPERMISSIONS_USERS => 'userId', Table::USERPERMISSIONS_USERGROUPS => 'groupId'] as $table => $column) {
                $ids = (new Query())->select('grants.' . $column)->from(['grants' => $table])
                    ->innerJoin(['permissions' => Table::USERPERMISSIONS], '[[permissions.id]] = [[grants.permissionId]]')
                    ->where(['permissions.name' => $legacyNames])->column();

                foreach (array_unique($ids) as $id) {
                    $this->db->createCommand()->upsert($table, [$column => $id, 'permissionId' => $permissionId], false)->execute();
                }
            }
        }

        if (method_exists(Craft::$app->getUserPermissions(), 'reset')) {
            Craft::$app->getUserPermissions()->reset();
        }

        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }
}
