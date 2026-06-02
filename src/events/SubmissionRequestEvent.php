<?php
namespace verbb\formie\events;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\SubmissionRequest;

use craft\events\CancelableEvent;

class SubmissionRequestEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public ?SubmissionRequest $request = null;
    
}
