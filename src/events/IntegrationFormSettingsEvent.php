<?php
namespace verbb\formie\events;

use verbb\formie\models\IntegrationFormSettings;

/** @deprecated in 4.0.0. Use IntegrationConfigEvent. */
class IntegrationFormSettingsEvent extends IntegrationConfigEvent
{
    // Properties
    // =========================================================================

    public ?IntegrationFormSettings $settings = null;
}
