<?php
namespace verbb\formie\events;

use verbb\formie\base\IntegrationInterface;

use yii\base\Event;

class ModifyFormIntegrationEvent extends Event
{
    // Properties
    // =========================================================================

    public ?IntegrationInterface $integration = null;
    
}
