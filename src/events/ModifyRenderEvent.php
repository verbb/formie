<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyRenderEvent extends Event
{
    // Properties
    // =========================================================================

    public ?string $html = null;
    
}
