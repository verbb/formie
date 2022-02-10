<?php
namespace verbb\formie\console\controllers;

use verbb\formie\Formie;

use yii\console\Controller;
use yii\console\ExitCode;

class GcController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Delete fields with no form.
     *
     * @return int
     * @throws Throwable
     */
    public function actionDeleteOrphanedFields()
    {
        Formie::$plugin->getFields()->deleteOrphanedFields($this);

        return ExitCode::OK;
    }

    /**
     * Delete syncs that are empty.
     *
     * @return int
     * @throws Throwable
     */
    public function actionPruneSyncs()
    {
        Formie::$plugin->getSyncs()->pruneSyncs($this);

        return ExitCode::OK;
    }

    /**
     * Delete incomplete submissions older than the configured interval.
     *
     * @return int
     * @throws Throwable
     */
    public function actionPruneIncompleteSubmissions()
    {
        Formie::$plugin->getSubmissions()->pruneIncompleteSubmissions($this);

        return ExitCode::OK;
    }

    /**
     * Deletes submissions if they are past the form data retention settings.
     *
     * @return int
     * @throws Throwable
     */
    public function actionPruneDataRetentionSubmissions()
    {
        Formie::$plugin->getSubmissions()->pruneDataRetentionSubmissions($this);

        return ExitCode::OK;
    }

    /**
     * Delete leftover content tables, for deleted forms.
     *
     * @return int
     * @throws Throwable
     */
    public function actionPruneContentTables()
    {
        Formie::$plugin->getForms()->pruneContentTables($this);

        return ExitCode::OK;
    }
}
