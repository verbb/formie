<?php
namespace verbb\formie\events;

use verbb\formie\elements\Submission;

use yii\base\Event;

class PruneSubmissionEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Submission $submission = null;
    
}
