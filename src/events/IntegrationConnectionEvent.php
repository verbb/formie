<?php
namespace verbb\formie\events;

use verbb\formie\base\Integration;

use craft\events\CancelableEvent;

class IntegrationConnectionEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public ?Integration $integration = null;
    public ?bool $success = null;
    
}
