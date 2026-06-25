<?php
namespace verbb\formie\migrations;

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

        foreach ($statuses as $uid => $status) {
            if (($status['handle'] ?? null) !== 'new' || ($status['color'] ?? null) !== 'green') {
                continue;
            }

            $projectConfig->set("$path.$uid.color", 'turquoise', 'Align default new submission status color with Craft');
        }

        return true;
    }

    public function safeDown(): bool
    {
        return true;
    }
}
