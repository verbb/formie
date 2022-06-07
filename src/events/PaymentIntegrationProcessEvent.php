<?php
namespace verbb\formie\events;

use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;

use craft\events\CancelableEvent;

class PaymentIntegrationProcessEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public ?Submission $submission = null;
    public ?Integration $integration = null;
    public ?bool $result = null;
    
}
