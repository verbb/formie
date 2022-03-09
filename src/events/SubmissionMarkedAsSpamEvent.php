<?php
namespace verbb\formie\events;

use verbb\formie\elements\Submission;

use craft\events\CancelableEvent;

class SubmissionMarkedAsSpamEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public ?Submission $submission = null;
    public bool $isNew = false;

}
