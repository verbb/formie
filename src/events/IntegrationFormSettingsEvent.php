<?php
namespace verbb\formie\events;

use craft\events\CancelableEvent;

class IntegrationFormSettingsEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public $integration;
    public $settings;
    
}
