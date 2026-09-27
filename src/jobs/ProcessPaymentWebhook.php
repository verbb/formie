<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;

use Craft;
use craft\queue\BaseJob;

class ProcessPaymentWebhook extends BaseJob
{
    // Properties
    // =========================================================================

    public int $receiptId;


    // Public Methods
    // =========================================================================

    public function execute($queue): void
    {
        Formie::$plugin->getPaymentWebhooks()->process($this->receiptId);
        $this->setProgress($queue, 1);
    }


    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): string
    {
        return Craft::t('formie', 'Processing a verified payment webhook.');
    }
}
