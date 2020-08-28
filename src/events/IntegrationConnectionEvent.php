<?php
namespace verbb\formie\events;

use craft\events\CancelableEvent;

class IntegrationConnectionEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public $integration;
    public $success;
    
}
