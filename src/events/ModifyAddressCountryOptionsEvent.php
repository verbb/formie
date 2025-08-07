<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyAddressCountryOptionsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?array $options = null;
    
}
