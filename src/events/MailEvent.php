<?php
namespace verbb\formie\events;

use verbb\formie\elements\Submission;
use verbb\formie\models\Notification;

use craft\events\CancelableEvent;

class MailEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public mixed $email = null;
    public ?Notification $notification = null;
    public ?Submission $submission = null;

}
