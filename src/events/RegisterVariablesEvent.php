<?php
namespace verbb\formie\events;

use verbb\formie\compatibility\variables\VariableSourceCompatibility;
use verbb\formie\variables\VariableSource;
use verbb\formie\variables\VariableSourceInterface;

use yii\base\Event;

class RegisterVariablesEvent extends Event
{
    // Properties
    // =========================================================================

    public array $sources = [];

}
