<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyIntegrationsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?array $integrations = null;
    
}
