<?php
namespace verbb\formie\events;

use verbb\formie\elements\Submission;

use yii\base\Event;

class SubmissionRulesEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Submission $submission = null;
    public ?array $rules = null;
    
}
