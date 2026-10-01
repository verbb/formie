<?php
namespace verbb\formie\console\controllers;

use verbb\formie\Formie;
use verbb\formie\services\SubmissionDispatches;

use craft\console\Controller;
use craft\db\Query;
use craft\helpers\Json;

use yii\console\ExitCode;

class DeliveriesController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(int $afterId = 0, int $limit = 100): int
    {
        $rows = (new Query())->select(['id', 'submissionId', 'uid', 'kind', 'status', 'schedulingComplete', 'failureCode'])
            ->from(SubmissionDispatches::TABLE)->where(['>', 'id', $afterId])->orderBy(['id' => SORT_ASC])->limit(max(1, min(500, $limit)))->all();

        foreach ($rows as $row) {
            $this->stdout(Json::encode($row) . PHP_EOL);
        }
        return ExitCode::OK;
    }

    public function actionRecover(int $limit = 100): int
    {
        $count = Formie::$plugin->getSubmissionDispatches()->recover($limit);
        $this->stdout("Scheduled {$count} submission dispatches.\n");
        return ExitCode::OK;
    }
}
