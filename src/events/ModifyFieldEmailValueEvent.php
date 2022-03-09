<?php
namespace verbb\formie\events;

use verbb\formie\base\FormField;
use verbb\formie\elements\Submission;
use verbb\formie\models\Notification;

use yii\base\Event;

class ModifyFieldEmailValueEvent extends Event
{
    // Properties
    // =========================================================================

    public mixed $value = null;
    public ?FormField $field = null;
    public ?Submission $submission = null;
    public ?Notification $notification = null;
    
}
