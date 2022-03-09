<?php
namespace verbb\formie\events;

use verbb\formie\elements\Submission;

use craft\events\CancelableEvent;

class SubmissionEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public ?Submission $submission;
    public ?bool $success;
    
}
