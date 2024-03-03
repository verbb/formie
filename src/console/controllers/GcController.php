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
}
