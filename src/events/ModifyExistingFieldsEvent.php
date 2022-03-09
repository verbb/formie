<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyExistingFieldsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?array $fields = null;
    
}
