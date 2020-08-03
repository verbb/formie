<?php
namespace verbb\formie\events;

use craft\events\CancelableEvent;

class TriggerElementEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public $submission;
    public $element;
    
}
