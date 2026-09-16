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


    // Public Methods
    // =========================================================================

    public function register(string $target, string $handle, string $label): VariableSource
    {
        return VariableSourceCompatibility::registerLegacySource($this, $target, $handle, $label);
    }
}
