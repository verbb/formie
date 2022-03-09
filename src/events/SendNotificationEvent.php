<?php
namespace verbb\formie\events;

use verbb\formie\elements\Submission;
use verbb\formie\models\Notification;

use craft\events\CancelableEvent;

class SendNotificationEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public ?Submission $submission = null;
    public ?Notification $notification = null;
    
}
