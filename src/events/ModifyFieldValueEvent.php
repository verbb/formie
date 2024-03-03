<?php
namespace verbb\formie\events;

use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Submission;

use yii\base\Event;

class ModifyFieldValueEvent extends Event
{
    // Properties
    // =========================================================================

    public mixed $value = null;
    public ?FieldInterface $field = null;
    public ?Submission $submission = null;
    
}
