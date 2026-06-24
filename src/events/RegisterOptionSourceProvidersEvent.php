<?php
namespace verbb\formie\events;

use yii\base\Event;

class RegisterOptionSourceProvidersEvent extends Event
{
    // Properties
    // =========================================================================

    public array $providers = [];
}
