<?php
namespace verbb\formie\events;

use craft\events\CancelableEvent;

class SendIntegrationPayloadEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public $submission;
    public $integration;
    public $payload;
    public $response;
    
}
