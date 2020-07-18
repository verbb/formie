<?php
namespace verbb\formie\events;

use craft\events\CancelableEvent;

class SubmissionMarkedAsSpamEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public $submission;
    public $isNew;

}
