<?php
namespace verbb\formie\events;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

use craft\events\CancelableEvent;

class SubmissionEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public ?Submission $submission = null;
    public ?Form $form = null;
    public ?string $submitAction = null;
    public ?bool $success = null;
    
}
