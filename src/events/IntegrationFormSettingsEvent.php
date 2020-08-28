<?php
namespace verbb\formie\events;

use yii\base\Event;

class IntegrationFormSettingsEvent extends Event
{
    // Properties
    // =========================================================================

    public $integration;
    public $settings;
    
}
