<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table as FormieTable;
use verbb\formie\integrations\elements\User as UserIntegration;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Db;
use craft\helpers\Json;

/**
 * `m241128_000000_user_group_integrations` only converted the user groups of the last form it read, so every other
 * form kept its legacy `groupIds` and lost its groups. Convert any that are still waiting.
 */
class m261124_300000_user_group_integration_uids extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $handles = (new Query())
            ->select(['handle'])
            ->from(FormieTable::FORMIE_INTEGRATIONS)
            ->where(['type' => UserIntegration::class])
            ->column($this->db);

        if (!$handles) {
            return true;
        }

        foreach ((new Query())->select(['id', 'settings'])->from(FormieTable::FORMIE_FORMS)->each(100, $this->db) as $row) {
            $settings = Json::decodeIfJson($row['settings']);

            if (!is_array($settings)) {
                continue;
            }

            $changed = false;

            foreach ($handles as $handle) {
                $binding = $settings['integrations'][$handle] ?? null;

                // Forms saved since then hold their own `groupUids`, even if empty, so leave those alone.
                if (!is_array($binding) || array_key_exists('groupUids', $binding) || !is_array($binding['groupIds'] ?? null)) {
                    continue;
                }

                $groupUids = [];

                foreach ($binding['groupIds'] as $groupId) {
                    if (is_numeric($groupId) && $groupUid = Db::uidById(Table::USERGROUPS, (int)$groupId, $this->db)) {
                        $groupUids[] = $groupUid;
                    }
                }

                if ($groupUids) {
                    $settings['integrations'][$handle]['groupUids'] = $groupUids;
                    $changed = true;

                    $message = "Form {$row['id']}: User integration `{$handle}` now assigns user groups `" . implode(', ', $groupUids) . '`, which an earlier upgrade left out.';
                    Craft::warning($message, __METHOD__);
                    echo "    > {$message}\n";
                }
            }

            if ($changed) {
                $this->update(FormieTable::FORMIE_FORMS, ['settings' => Json::encode($settings)], ['id' => $row['id']], [], false);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261124_300000_user_group_integration_uids cannot be reverted.\n";

        return false;
    }
}
