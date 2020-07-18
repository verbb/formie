<?php
namespace verbb\formie\events;

use yii\base\Event;

class SubmissionEvent extends Event
{
    // Properties
    // =========================================================================

    public $submission;
    public $success;
    
}
