<?php
namespace verbb\formie\events;

use yii\base\Event;

class RegisterVariablesEvent extends Event
{
    // Properties
    // =========================================================================

    public array $variables = [];
    
}
