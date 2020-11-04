<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;
use verbb\formie\base\Element;

use Craft;
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

        if (!$response) {
            throw new \Exception('Failed to trigger integration.');
        }

        $this->setProgress($queue, 1);
    }
}
