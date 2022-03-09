<?php
namespace verbb\formie\events;

use craft\events\CancelableEvent;
use verbb\formie\elements\Form;
use verbb\formie\models\Notification;

class ModifyMigrationNotificationEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public ?Form $form = null;
    public mixed $notification = null;
    public ?Notification $newNotification = null;
    
}
