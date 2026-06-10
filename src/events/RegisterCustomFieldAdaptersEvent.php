<?php
namespace verbb\formie\events;

use yii\base\Event;

class RegisterCustomFieldAdaptersEvent extends Event
{
    // Properties
    // =========================================================================

    public array $adapters = [];
}
