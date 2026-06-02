<?php
namespace verbb\formie\events;

use verbb\formie\elements\Submission;

use craft\events\CancelableEvent;

class SubmissionPostActionEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public ?Submission $submission = null;
    public ?string $submitAction = null;
    public ?bool $success = null;
}
