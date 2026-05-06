<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyAutocompleteOptionsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?array $options = null;
    
}

