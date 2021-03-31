<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;
use verbb\formie\base\Element;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationResponse;

use Craft;
use craft\helpers\Json;
use craft\queue\BaseJob;

class TriggerIntegration extends BaseJob
{
    // Public Properties
    // =========================================================================

    public $submissionId;
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
}
