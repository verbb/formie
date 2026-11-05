<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;

use Craft;
use craft\helpers\Queue;
use craft\queue\BaseJob;

class RebuildUploadReferences extends BaseJob
{
    // Properties
    // =========================================================================

    public int $formId;

    // Public Methods
    // =========================================================================

    public function execute($queue): void
    {
        if (!Formie::$plugin->getUploadReferences()->rebuildBatch($this->formId)) {
            Queue::push(new self(['formId' => $this->formId]));
        }
    }

    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): string
    {
        return Craft::t('formie', 'Indexing submission uploads.');
    }
}
