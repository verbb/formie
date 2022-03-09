<?php
namespace verbb\formie\events;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

use craft\events\CancelableEvent;

class ModifyMigrationSubmissionEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public ?Form $form = null;
    public ?Submission $submission = null;
    
}
