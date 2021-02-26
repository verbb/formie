<?php
namespace verbb\formie\events;

use yii\base\Event;

class SubmissionRulesEvent extends Event
{
    // Properties
    // =========================================================================

    public $submission;
    public $rules;
    
}
