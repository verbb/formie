<?php
namespace verbb\formie\events;

use craft\events\CancelableEvent;

class SubmissionEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public $submission;
    public $success;
    
}
