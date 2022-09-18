<?php
namespace verbb\formie\events;

use verbb\formie\elements\Submission;

use yii\base\Event;

class ModifyPayloadEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Submission $submission = null;
    public ?array $payload = null;
    
}
