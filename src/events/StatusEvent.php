<?php
namespace verbb\formie\events;

use yii\base\Event;

class StatusEvent extends Event
{
    // Properties
    // =========================================================================

    public $status;
    public $isNew = false;
    
}
