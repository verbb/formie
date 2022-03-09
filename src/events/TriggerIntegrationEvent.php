<?php
namespace verbb\formie\events;

use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;

use craft\events\CancelableEvent;

class TriggerIntegrationEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public ?Submission $submission = null;
    public ?string $type = null;
    public ?Integration $integration = null;
    
}
