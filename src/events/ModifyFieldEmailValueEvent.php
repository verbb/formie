<?php
namespace verbb\formie\events;

use verbb\formie\base\FormFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\models\Notification;

use yii\base\Event;

class ModifyFieldEmailValueEvent extends Event
{
    // Properties
    // =========================================================================

    public mixed $value = null;
    public ?FormFieldInterface $field = null;
    public ?Submission $submission = null;
    public ?Notification $notification = null;
    
}
