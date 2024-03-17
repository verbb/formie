<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifySubFieldsEvent extends Event
{
    // Properties
    // =========================================================================

    public array $fields = [];
    
}
