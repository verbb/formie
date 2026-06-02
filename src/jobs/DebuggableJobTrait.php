<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Query;
use craft\helpers\Db;

use Throwable;

use yii\queue\ExecEvent;

trait DebuggableJobTrait
{
    public function onError(ExecEvent $event): void
    {
        // Craft serializes queue jobs before execution, so failure-only debug
        // details need to be written back to the stored queue row explicitly.
        try {
            $jobData = (new Query())
                ->select(['job'])
                ->from(Table::QUEUE)
                ->where(['id' => $event->id])
                ->scalar();

            if (!$jobData) {
                return;
            }

            $jobData = Craft::$app->getQueue()->serializer->unserialize($jobData);

            $this->updateDebugJobData($event->job, $jobData);

            $jobData = Craft::$app->getQueue()->serializer->serialize($jobData);

            Db::update(Table::QUEUE, ['job' => $jobData], ['id' => $event->id], [], false);
        } catch (Throwable $e) {
            Formie::error('Unable to update job info debug: “{message}” {file}:{line}. Trace: “{trace}”', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function updateDebugJobData(mixed $job, mixed $jobData): void
    {
    }
}
