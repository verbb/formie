<?php
namespace verbb\formie\events;

use verbb\formie\models\Notification;

use yii\base\Event;

class NotificationEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Notification $notification = null;
    public bool $isNew = false;
    
}
