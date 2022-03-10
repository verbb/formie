<?php
namespace verbb\formie\events;

use verbb\formie\base\FormFieldInterface;
use verbb\formie\elements\NestedFieldRow;
use verbb\formie\elements\Submission;

use yii\base\Event;

class ModifyFieldValueEvent extends Event
{
    // Properties
    // =========================================================================

    public mixed $value = null;
    public ?FormFieldInterface $field = null;
    public Submission|NestedFieldRow|null $submission = null;
    
}
