<?php
namespace verbb\formie\events;

use craft\events\CancelableEvent;

class TriggerIntegrationEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public $submission;
    public $type;
    public $integration;
    
}
