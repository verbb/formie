<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;

use Craft;
use craft\base\Element;
use craft\db\Table;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\queue\BaseJob as CraftBaseJob;
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
            // Ensure that the payload is simplified a little. For some instances `serialize()` can't handle Closures
            // and sometimes the payload is a Craft element, which contains them (potentially).
            if (property_exists($event->job, 'payload')) {
                $payload = Json::decode(Json::encode($event->job->payload));

                // Add in custom fields with a bit more context
                if ($event->job->payload instanceof Element) {
                    if ($fieldLayout = $event->job->payload->getFieldLayout()) {
                        foreach ($fieldLayout->getCustomFields() as $field) {
                            $payload['fields'][] = [
                                'type' => get_class($field),
                                'handle' => $field->handle,
                                'value' => $event->job->payload->getFieldValue($field->handle),
                            ];
                        }
                    }
                }

                $event->job->payload = $payload;
            }

            // For integrations, we need to serialize the entire class, but after initial push to the job
            // there are potentially properties that contain Closures. This which will choke using `serialize()`.
            // Delete anything that could be an issue. Would be nice if Craft itself handled this?
            if (property_exists($event->job, 'integration')) {
                $event->job->integration->setClient(null);

                // Clear out the cache for the same reason (can get immensely large)
                $event->job->integration->cache = null;
            }

            // Serialize it again ready to save
            $message = Craft::$app->getQueue()->serializer->serialize($event->job);

            Db::update(Table::QUEUE, ['job' => $message], ['id' => $event->id], [], false);
        } catch (Throwable $e) {
            Formie::error(Craft::t('formie', 'Unable to update job info debug: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));
        }
    }
}
