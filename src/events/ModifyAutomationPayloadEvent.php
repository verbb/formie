<?php
namespace verbb\formie\events;

use verbb\formie\elements\Submission;

use yii\base\Event;

class ModifyAutomationPayloadEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Submission $submission = null;
    public mixed $payload = null;
    
}
