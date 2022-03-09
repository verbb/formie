<?php
namespace verbb\formie\events;

use verbb\formie\base\FormField;
use verbb\formie\elements\Submission;

use yii\base\Event;

class ModifyFieldValueEvent extends Event
{
    // Properties
    // =========================================================================

    public mixed $value = null;
    public ?FormField $field = null;
    public ?Submission $submission = null;
    
}
