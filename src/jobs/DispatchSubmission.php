<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;

use Craft;
use craft\queue\BaseJob;

/** @deprecated Nothing queues this any more. It remains so jobs queued by an earlier release can finish. */
class DispatchSubmission extends BaseJob
{
    // Properties
    // =========================================================================

    public int $submissionId;
    public string $dispatchUid;


    // Public Methods
    // =========================================================================

    public function execute($queue): void
    {
        Formie::$plugin->getSubmissionDispatches()->resume($this->submissionId, $this->dispatchUid);
        $this->setProgress($queue, 1);
    }


    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): string
    {
        return Craft::t('formie', 'Recovering submission delivery.');
    }
}
