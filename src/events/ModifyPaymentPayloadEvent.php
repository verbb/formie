<?php
namespace verbb\formie\events;

use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;

use yii\base\Event;

class ModifyPaymentPayloadEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Submission $submission = null;
    public ?Integration $integration = null;
    public ?array $payload = null;
    
}
