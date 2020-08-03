<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;

use Craft;
use craft\queue\BaseJob;

class TriggerIntegration extends BaseJob
{
    // Public Properties
    // =========================================================================

    /**
     * @var int
     */
    public $submissionId;

    /**
     * @var int
     */
    public $element;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Triggering form “{handle}” integration.', ['handle' => $this->element->handle]);
    }

    /**
     * @inheritDoc
     */
    public function execute($queue)
    {
        $this->setProgress($queue, 0);

        $submission = Formie::$plugin->getSubmissions()->getSubmissionById($this->submissionId);

        $response = $this->element->saveElement($submission);

        if (!$response) {
            throw new \Exception('Failed to trigger integration.');
        }

        $this->setProgress($queue, 1);
    }
}
