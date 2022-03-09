<?php
namespace verbb\formie\events;

use verbb\formie\elements\Submission;

use yii\base\Event;

class ModifyWebhookPayloadEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Submission $submission = null;
    public ?array $payload = null;
    
}
