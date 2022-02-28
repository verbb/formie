<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyEmailFieldUniqueQueryEvent extends Event
{
    // Properties
    // =========================================================================

    public $query;
    public $field;
    
}
