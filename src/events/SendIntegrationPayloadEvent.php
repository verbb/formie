<?php
namespace verbb\formie\events;

use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;

use craft\events\CancelableEvent;

class SendIntegrationPayloadEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public ?Submission $submission = null;
    public ?Integration $integration = null;
    public mixed $payload = null;
    public mixed $response = null;
    public ?string $endpoint = null;
    public ?string $method = null;
    
}
