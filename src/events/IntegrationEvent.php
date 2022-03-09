<?php
namespace verbb\formie\events;

use verbb\formie\base\Integration;

use yii\base\Event;

class IntegrationEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Integration $integration = null;
    public bool $isNew = false;
    
}
