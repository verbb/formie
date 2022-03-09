<?php
namespace verbb\formie\events;

use verbb\formie\elements\Submission;
use verbb\formie\models\Notification;

use yii\base\Event;

class MailRenderEvent extends Event
{
    // Properties
    // =========================================================================

    public mixed $email = null;
    public ?Notification $notification = null;
    public ?Submission $submission = null;
    public ?array $renderVariables = null;

}
