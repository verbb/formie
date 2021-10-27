<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyFieldSummaryContentEvent extends Event
{
    // Properties
    // =========================================================================

    public $value;
    public $element;
    
}
