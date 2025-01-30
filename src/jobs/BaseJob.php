<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\Table;

use Craft;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\queue\BaseJob as CraftBaseJob;

use Closure;
use ReflectionObject;
use Throwable;

abstract class BaseJob extends CraftBaseJob
{
    // Public Methods
    // =========================================================================

    public function updatePayload($event): void
    {
        // When an error occurs on the job, we want to update the Job Data for the job. This helps immensely with
        // debugging, and provides the customer with context on exactly _what_ is trying to be sent.
        // We have to do a direct database update however, because the Job Data is only serialized when the job 
        // is created. The payload is changed via multiple calls in the task, so we want to reflect that,
        try {
            // Just check that we've got a payload property for this integration
            if (!property_exists($event->job, 'payload')) {
                return;
            }

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
            $payload = $event->job->payload;

            // For element integrations, add in custom fields with a bit more context
            if ($payload instanceof ElementInterface) {
                $element = $event->job->payload;
                $payload = Json::decode(Json::encode($payload));

                if ($fieldLayout = $element->getFieldLayout()) {
                    foreach ($fieldLayout->getCustomFields() as $field) {
                        $payload['fields'][] = [
                            'type' => get_class($field),
                            'handle' => $field->handle,
                            'value' => $element->getFieldValue($field->handle),
                        ];
                    }
                }
            }

            $jobData->payload = $payload;
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
}
