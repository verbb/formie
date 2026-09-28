<?php
namespace verbb\formie\events;

use verbb\formie\base\Integration;
use verbb\formie\models\IntegrationConfig;

use craft\events\CancelableEvent;

class IntegrationConfigEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public ?Integration $integration = null;
    public ?IntegrationConfig $config = null;
}
