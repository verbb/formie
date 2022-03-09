<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyFormIntegrationsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?array $integrations = null;
    public ?string $type = null;
    
}
