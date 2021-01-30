<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyFieldValueForIntegrationEvent extends Event
{
    // Properties
    // =========================================================================

    public $field;
    public $value;
    public $submission;
    
}
