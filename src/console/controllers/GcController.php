<?php
namespace verbb\formie\console\controllers;

use verbb\formie\Formie;

use yii\console\Controller;
use yii\console\ExitCode;
use Throwable;
use yii\db\Exception;
use yii\db\StaleObjectException;

class GcController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionPruneIncompleteSubmissions(): int
    {
        Formie::$plugin->getSubmissions()->pruneIncompleteSubmissions($this);

        return ExitCode::OK;
    }

    public function actionPruneDataRetentionSubmissions(): int
    {
        Formie::$plugin->getSubmissions()->pruneDataRetentionSubmissions($this);

        return ExitCode::OK;
    }
}
