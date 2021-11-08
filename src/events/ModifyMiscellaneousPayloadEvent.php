<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyMiscellaneousPayloadEvent extends Event
{
    // Properties
    // =========================================================================

    public $submission;
    public $payload;
    
}
