<?php
namespace verbb\formie\events;

use yii\base\Event;

class MailRenderEvent extends Event
{
    // Properties
    // =========================================================================

    public $email;
    public $notification;
    public $submission;
    public $renderVariables;

}
