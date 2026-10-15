<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\services\SubmissionStatuses;

use Craft;
use craft\db\Migration;

class m260641_000000_align_default_submission_status_colors extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $path = SubmissionStatuses::CONFIG_SUBMISSION_STATUSES_KEY;
        $statuses = $projectConfig->get($path, true) ?? [];
        $muteEvents = $projectConfig->muteEvents;
        $updated = false;

        try {
            // This system migration updates both stores directly so status listeners cannot
            // turn a compatibility deprecation into a failed plugin upgrade.
            $projectConfig->muteEvents = true;

            foreach ($statuses as $uid => $status) {
                if (($status['handle'] ?? null) !== 'new' || ($status['color'] ?? null) !== 'green') {
                    continue;
                }

                $projectConfig->set("$path.$uid.color", 'turquoise', 'Align default new submission status color with Craft');
                $this->update(Table::FORMIE_SUBMISSION_STATUSES, ['color' => 'turquoise'], ['uid' => $uid]);
                $updated = true;
            }

            if ($updated) {
                Formie::$plugin->getSubmissionStatuses()->invalidateCaches();
            }
        } finally {
            $projectConfig->muteEvents = $muteEvents;
        }

        return true;
    }

    public function safeDown(): bool
    {
        return true;
    }
}
