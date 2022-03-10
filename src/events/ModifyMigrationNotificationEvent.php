<?php
namespace verbb\formie\events;

use verbb\formie\elements\Form;
use verbb\formie\models\Notification;

use craft\events\CancelableEvent;

class ModifyMigrationNotificationEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public ?Form $form = null;
    public mixed $notification = null;
    public ?Notification $newNotification = null;
    
}
