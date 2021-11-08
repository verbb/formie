<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyFieldValueEvent extends Event
{
    // Properties
    // =========================================================================

    public $value;
    public $field;
    public $element;
    
}
