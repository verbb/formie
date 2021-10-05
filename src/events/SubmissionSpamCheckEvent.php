<?php
namespace verbb\formie\events;

use yii\base\Event;

class SubmissionSpamCheckEvent extends Event
{
    // Properties
    // =========================================================================

    public $submission;
    
}
