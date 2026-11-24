<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\models\FormGroupSettings;
use verbb\formie\services\FormGroups;

use Craft;
use craft\db\Migration;

class m261125_000000_form_group_config_uids extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        // Config that has already reached this version stores UIDs, and is applied as it is.
        if ($projectConfig->readOnly || version_compare($projectConfig->get('plugins.formie.schemaVersion', true) ?? '0', '4.0.118', '>=')) {
            return true;
        }

        $muteEvents = $projectConfig->muteEvents;
        $projectConfig->muteEvents = true;

        try {
            // Group settings stored this environment's status and site IDs, so they still name what the group was set up with here.
            foreach ($projectConfig->get(FormGroups::CONFIG_GROUPS_KEY) ?? [] as $groupUid => $groupConfig) {
                $settings = $groupConfig['settings'] ?? null;

                if (!is_array($settings)) {
                    continue;
                }

                $sitePolicy = $settings['sitePolicy'] ?? null;

                if (!array_key_exists('allowedStatusIds', $settings) && !(is_array($sitePolicy) && array_key_exists('enabledSiteIds', $sitePolicy))) {
                    continue;
                }

                $projectConfig->set(FormGroups::CONFIG_GROUPS_KEY . '.' . $groupUid . '.settings', FormGroupSettings::toConfig($settings), 'Store form group statuses and sites by UID');

                // Whichever environment runs this first decides what the IDs meant, so show what it chose.
                $parts = [];

                if (array_key_exists('allowedStatusIds', $settings)) {
                    $names = array_map(fn(int $id) => Formie::$plugin->getSubmissionStatuses()->getStatusById($id)?->name ?? "missing status {$id}", self::_ids($settings['allowedStatusIds']));
                    $parts[] = 'Allowed Submission Statuses as `' . ($names ? implode(', ', $names) : 'All') . '`';
                }

                if (is_array($sitePolicy) && array_key_exists('enabledSiteIds', $sitePolicy)) {
                    $handles = array_map(fn(int $id) => Craft::$app->getSites()->getSiteById($id, true)?->handle ?? "missing site {$id}", self::_ids($sitePolicy['enabledSiteIds']));
                    $parts[] = 'Enabled Sites as `' . ($handles ? implode(', ', $handles) : 'All') . '`';
                }

                $message = 'Form group `' . ($groupConfig['handle'] ?? $groupUid) . '` now stores ' . implode(' and ', $parts) . ' by UID. Check these are the statuses and sites the group was set up with before committing `config/project`.';
                Craft::warning($message, __METHOD__);
                echo "    > {$message}\n";
            }
        } finally {
            $projectConfig->muteEvents = $muteEvents;
        }

        Formie::$plugin->getFormGroups()->invalidateCaches();

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261125_000000_form_group_config_uids cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private static function _ids(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $value))));
    }
}
