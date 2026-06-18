<?php
namespace verbb\formie\console\controllers;

use verbb\formie\Formie;

use craft\console\Controller;
use craft\helpers\Console;

use Throwable;

use yii\console\ExitCode;
use yii\db\Exception;
use yii\db\StaleObjectException;

/**
 * Manages Formie cleanup utilities and jobs.
 */
class GcController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Removes any incomplete submissions.
     */
    public function actionPruneIncompleteSubmissions(): int
    {
        Formie::$plugin->getSubmissions()->pruneIncompleteSubmissions($this);

        return ExitCode::OK;
    }

    /**
     * Removes any submissions that have passed their data retention setting.
     */
    public function actionPruneDataRetentionSubmissions(): int
    {
        Formie::$plugin->getSubmissions()->pruneDataRetentionSubmissions($this);

        return ExitCode::OK;
    }

    /**
     * Removes uploaded assets that exceed a File Upload field's asset retention setting.
     */
    public function actionPruneFileUploadAssetRetention(): int
    {
        $count = Formie::$plugin->getFileUploads()->pruneExpiredFieldAssets($this);
        $this->stdout('Purged uploaded assets: ' . $count . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Removes stale non-finalized pending uploads.
     */
    public function actionPruneStalePendingUploads(): int
    {
        $count = Formie::$plugin->getFileUploads()->purgeStalePendingUploads();
        $this->stdout('Purged stale pending uploads: ' . $count . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Removes expired report export files.
     */
    public function actionPruneReportExports(): int
    {
        $count = Formie::$plugin->getReportExportFiles()->pruneExpired();
        $this->stdout('Purged expired report exports: ' . $count . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Removes stale submission states.
     */
    public function actionPruneSubmissionStates(): int
    {
        $count = Formie::$plugin->getSubmissionDrafts()->pruneDraftStates();
        $this->stdout('Pruned submission states: ' . $count . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }
}
