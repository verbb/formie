<?php
namespace verbb\formie\events;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\Notification;

use yii\base\Event;

class ParseVariablesEvent extends Event
{
    // Properties
    // =========================================================================

    public mixed $value;
    public ?Submission $submission = null;
    public ?Form $form = null;
    public ?Notification $notification = null;
    public array $variables = [];
    
}
