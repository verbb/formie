<?php
namespace verbb\formie\events;

use verbb\formie\base\FormFieldInterface;
use verbb\formie\elements\NestedFieldRow;
use verbb\formie\elements\Submission;
use verbb\formie\models\FakeElement;

use yii\base\Event;

class ModifyFieldValueEvent extends Event
{
    // Properties
    // =========================================================================

    public mixed $value = null;
    public ?FormFieldInterface $field = null;
    public Submission|NestedFieldRow|FakeElement|null $submission = null;
    
}
