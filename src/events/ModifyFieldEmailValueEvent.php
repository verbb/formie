<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyFieldEmailValueEvent extends Event
{
    // Properties
    // =========================================================================

    public $value;
    public $field;
    public $submission;
    public $notification;

}
