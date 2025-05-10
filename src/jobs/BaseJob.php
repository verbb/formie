<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Query;
use craft\helpers\Db;
use craft\queue\BaseJob as CraftBaseJob;

use Throwable;

use yii\queue\ExecEvent;

abstract class BaseJob extends CraftBaseJob
{
    // Public Methods
    // =========================================================================

    public function onError(ExecEvent $event): void
    {
        // When an error occurs on the job, we want to update the Job Data for the job. This helps immensely with
        // debugging, and provides the customer with context on exactly _what_ is trying to be sent.
        // We have to do a direct database update however, because the Job Data is only serialized when the job 
        // is created. The payload is changed via multiple calls in the task, so we want to reflect that,
        try {
            // Get the serialized job data for this job directly from the database
            $jobData = (new Query())
                ->select(['job'])
                ->from(Table::QUEUE)
                ->where(['id' => $event->id])
                ->scalar();

            if (!$jobData) {
                return;
            }

            // Modify the serialized content of a job to add in just the payload data.
            $jobData = Craft::$app->getQueue()->serializer->unserialize($jobData);

            // Allow job classes to update the job data
            $this->handleError($event->job, $jobData);

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

    
    // Protected Methods
    // =========================================================================

    protected function handleError(mixed $job, mixed $jobData): void
    {

    }
}
