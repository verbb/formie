<?php
namespace verbb\formie\events;

use craft\events\CancelableEvent;

class MailEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public $email;

}
