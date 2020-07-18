<?php
namespace verbb\formie\events;

use yii\base\Event;

class FieldPageEvent extends Event
{
    // Properties
    // =========================================================================

    public $page;
    public $isNew = false;
    
}
