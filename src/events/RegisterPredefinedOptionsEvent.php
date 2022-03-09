<?php
namespace verbb\formie\events;

use yii\base\Event;

class RegisterPredefinedOptionsEvent extends Event
{
    // Properties
    // =========================================================================

    public array $options = [];
}
