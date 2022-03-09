<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyNamePrefixOptionsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?array $options = null;
    
}
