<?php
namespace verbb\formie\jobs;

/** Associates a queue job with durable delivery evidence without rewriting the job. */
interface DeliveryJobInterface extends DebuggableJobInterface
{
    // Public Methods
    // =========================================================================

    public function getDeliveryAttemptUid(): string;
}
