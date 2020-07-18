<?php
namespace verbb\formie\events;

use yii\base\Event;

class EmailEvent extends Event
{
    // Properties
    // =========================================================================

    public $email;
    public $isNew = false;
    
}
