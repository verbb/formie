<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyExistingNotificationsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?array $notifications = null;
    
}
