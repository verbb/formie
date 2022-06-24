<?php
namespace verbb\formie\events;

use craft\events\CancelableEvent;

class ModifyMigrationNotificationEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public $form;
    public $notification;
    public $newNotification;

}
