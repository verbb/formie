<?php
namespace verbb\formie\events;

use yii\base\Event;

class NotificationEvent extends Event
{
    // Properties
    // =========================================================================

    public $notification;
    public $isNew = false;
    
}
