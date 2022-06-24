<?php
namespace verbb\formie\events;

use craft\events\CancelableEvent;

class SendNotificationEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public $submission;
    public $notification;

}
