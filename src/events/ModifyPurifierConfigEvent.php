<?php
namespace verbb\formie\events;

use yii\base\Event;

use HTMLPurifier_Config;

class ModifyPurifierConfigEvent extends Event
{
    // Properties
    // =========================================================================

    public ?HTMLPurifier_Config $config = null;
    
}
