<?php
namespace verbb\formie\console\controllers;

use verbb\formie\Formie;

use yii\console\Controller;
use yii\console\ExitCode;

class GcController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionDeleteOrphanedFields()
    {
        // Delete fields with no form.
        Formie::$plugin->getFields()->deleteOrphanedFields();

        return ExitCode::OK;
    }

    public function actionPruneSyncs()
    {
        // Delete syncs that are empty.
        Formie::$plugin->getSyncs()->pruneSyncs();

        return ExitCode::OK;
    }

    public function actionPruneIncompleteSubmissions()
    {
        // Delete incomplete submissions older than the configured interval.
        Formie::$plugin->getSubmissions()->pruneIncompleteSubmissions();

        return ExitCode::OK;
    }

    public function actionPruneDataRetentionSubmissions()
    {
        // Deletes submissions if they are past the form data retention settings.
        Formie::$plugin->getSubmissions()->pruneDataRetentionSubmissions($this);

        return ExitCode::OK;
    }

    public function actionPruneContentTables()
    {
        // Delete leftover content tables, for deleted forms
        Formie::$plugin->getForms()->pruneContentTables();

        return ExitCode::OK;
    }
}
