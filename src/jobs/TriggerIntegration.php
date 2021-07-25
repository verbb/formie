<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;
use verbb\formie\base\Element;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationResponse;

use Craft;
use craft\db\Table;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\queue\BaseJob;

class TriggerIntegration extends BaseJob
{
    // Public Properties
    // =========================================================================

    public $submissionId;
    public $payload;
    public $integration;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Triggering form “{handle}” integration.', ['handle' => $this->integration->handle]);
    }

    /**
     * @inheritDoc
     */
    public function execute($queue)
    {
        $this->setProgress($queue, 0);

        // Allow incomplete submissions
        $submission = Submission::find()
            ->id($this->submissionId)
            ->isIncomplete(null)
            ->one();

        // Pass a reference of this class to the integration, to assist with debugging.
        // Set with a private variable, so it doesn't appear in the queue job data which would be mayhem.
        $this->integration->setQueueJob($this);

        $response = Formie::$plugin->getSubmissions()->sendIntegrationPayload($this->integration, $submission);

        // Check if some integrations return a response object for more detail
        if ($response instanceof IntegrationResponse) {
            if (!$response->success) {
                throw new \Exception('Failed to trigger integration: ' . Json::encode($response->message) . '.');
            }
        }

        if (!$response) {
            throw new \Exception('Failed to trigger integration. Check the Formie log files.');
        }

        $this->setProgress($queue, 1);
    }

    /**
     * @inheritDoc
     */
    public function updatePayload($event)
    {
        // When an error occurs on the job, we want to update the Job Data for the job. This helps immensly with
        // debugging, and provides the customer with context on exactly _what_ is trying to be sent.
        // We have to do a direct database update however, because the Job Data is only serialized when the job 
        // is created. The payload is changed via multiple calls in the task, so we want to reflect that,
        $jobData = $this->_jobData($event->job);

        // Serialize it again ready to save
        $message = Craft::$app->getQueue()->serializer->serialize($jobData);

        Db::update(Table::QUEUE, ['job' => $message], ['id' => $event->id], [], false);
    }


    // Private Methods
    // =========================================================================

    /**
     * Checks if $job is a resource and if so, convert it to a serialized format.
     *
     * @param string|resource $job
     * @return string
     */
    private function _jobData($job)
    {
        if (is_resource($job)) {
            $job = stream_get_contents($job);

            if (is_string($job) && strpos($job, 'x') === 0) {
                $hex = substr($job, 1);
                if (StringHelper::isHexadecimal($hex)) {
                    $job = hex2bin($hex);
                }
            }
        }

        return $job;
    }
}
