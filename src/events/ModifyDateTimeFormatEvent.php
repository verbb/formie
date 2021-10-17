<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyDateTimeFormatEvent extends Event
{
    // Properties
    // =========================================================================

    public $field;
    public $dateFormat;
    public $timeFormat;
    
}
