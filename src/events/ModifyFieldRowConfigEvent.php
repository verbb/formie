<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyFieldRowConfigEvent extends Event
{
    // Properties
    // =========================================================================

    public ?array $config = null;
    
}
