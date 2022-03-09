<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyPurifierConfigEvent extends Event
{
    // Properties
    // =========================================================================

    public ?array $config = null;
    
}
