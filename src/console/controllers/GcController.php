<?php
namespace verbb\formie\console\controllers;

use verbb\formie\Formie;
use verbb\formie\services\Cleanup;

use craft\console\Controller;
use craft\helpers\Console;

use yii\console\ExitCode;

/**
 * Manages Formie cleanup utilities and jobs.
 */
class GcController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @var string|null Comma-separated cleanup task handles. Omit to run all tasks. See Cleanup::taskHandles().
     */
    public ?string $only = null;


    // Public Methods
    // =========================================================================

    public function options($actionID): array
    {
        $options = parent::options($actionID);

        if (in_array($actionID, ['run', 'index'], true)) {
            $options[] = 'only';
        }

        return $options;
    }

    /**
     * Runs all Formie cleanup tasks.
     *
     * Schedule this command on cron for production sites — for example, daily:
     *
     * ```
     * ./craft formie/gc/run
     * ```
     *
     * Or use `./craft formie/cron/run` to also run scheduled reports in one cron entry.
     */
    public function actionRun(): int
    {
        $this->stdout("Running Formie cleanup tasks ...\n", Console::FG_YELLOW);
        Formie::$plugin->getCleanup()->runAll($this, $this->_resolveOnly());
        $this->stdout('Finished Formie cleanup tasks.' . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Removes any incomplete submissions.
     */
    public function actionPruneIncompleteSubmissions(): int
    {
        Formie::$plugin->getCleanup()->runTask(Cleanup::TASK_INCOMPLETE_SUBMISSIONS, $this);

        return ExitCode::OK;
    }

    /**
     * Removes any submissions that have passed their data retention setting.
     */
    public function actionPruneDataRetentionSubmissions(): int
    {
        Formie::$plugin->getCleanup()->runTask(Cleanup::TASK_DATA_RETENTION_SUBMISSIONS, $this);

        return ExitCode::OK;
    }

    /**
     * Removes sent notifications that exceed the plugin's maximum age setting.
     */
    public function actionPruneSentNotifications(): int
    {
        Formie::$plugin->getCleanup()->runTask(Cleanup::TASK_SENT_NOTIFICATIONS, $this);

        return ExitCode::OK;
    }

    /**
     * Removes uploaded assets that exceed a File Upload field's asset retention setting.
     */
    public function actionPruneFileUploadAssetRetention(): int
    {
        Formie::$plugin->getCleanup()->runTask(Cleanup::TASK_FILE_UPLOAD_ASSET_RETENTION, $this);

        return ExitCode::OK;
    }

    /**
     * Removes stale non-finalized pending uploads.
     */
    public function actionPruneStalePendingUploads(): int
    {
        Formie::$plugin->getCleanup()->runTask(Cleanup::TASK_STALE_PENDING_UPLOADS, $this);

        return ExitCode::OK;
    }

    /**
     * Removes expired report export files.
     */
    public function actionPruneReportExports(): int
    {
        Formie::$plugin->getCleanup()->runTask(Cleanup::TASK_REPORT_EXPORTS, $this);

        return ExitCode::OK;
    }

    /**
     * Removes stale submission states.
     */
    public function actionPruneSubmissionStates(): int
    {
        Formie::$plugin->getCleanup()->runTask(Cleanup::TASK_SUBMISSION_STATES, $this);

        return ExitCode::OK;
    }

    /**
     * Removes expired submission draft storage rows.
     */
    public function actionPruneDraftStorage(): int
    {
        Formie::$plugin->getCleanup()->runTask(Cleanup::TASK_DRAFT_STORAGE, $this);

        return ExitCode::OK;
    }


    // Private Methods
    // =========================================================================

    /**
     * @return string[]|null
     */
    private function _resolveOnly(): ?array
    {
        if ($this->only === null || $this->only === '') {
            return null;
        }

        return array_values(array_filter(array_map('trim', explode(',', $this->only))));
    }
}
