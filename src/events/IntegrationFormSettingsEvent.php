<?php
namespace verbb\formie\events;

use verbb\formie\base\Integration;
use verbb\formie\models\IntegrationFormSettings;

use craft\events\CancelableEvent;

class IntegrationFormSettingsEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public ?Integration $integration = null;
    public ?IntegrationFormSettings $settings = null;
    
}
