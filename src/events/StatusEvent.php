<?php
namespace verbb\formie\events;

use yii\base\Event;

class StatusEvent extends Event
{
    // Properties
    // =========================================================================

    public ?string $status = null;
    public bool $isNew = false;
    
}
