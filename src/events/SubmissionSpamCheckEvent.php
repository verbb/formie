<?php
namespace verbb\formie\events;

use verbb\formie\elements\Submission;

use yii\base\Event;

class SubmissionSpamCheckEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Submission $submission;
    
}
