<?php
namespace verbb\formie\fields\data;

use verbb\formie\base\FieldValueInterface;

use craft\base\Serializable;

class OptionData implements FieldValueInterface, Serializable
{
    // Properties
    // =========================================================================

    public ?string $label = null;
    public ?string $value = null;
    public bool $selected;
    public bool $valid;

    
    // Public Methods
    // =========================================================================

    public function __construct(?string $label, ?string $value, bool $selected, bool $valid = true)
    {
        $this->label = $label;
        $this->value = $value;
        $this->selected = $selected;
        $this->valid = $valid;
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }

    public function serialize(): mixed
    {
        return $this->value;
    }
}
