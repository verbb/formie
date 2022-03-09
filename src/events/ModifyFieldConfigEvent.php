<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyFieldConfigEvent extends Event
{
    // Properties
    // =========================================================================

    public ?array $config = null;
    
}
