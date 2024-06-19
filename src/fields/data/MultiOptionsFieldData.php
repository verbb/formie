<?php
namespace verbb\formie\fields\data;

use ArrayObject;

class MultiOptionsFieldData extends ArrayObject
{
    // Properties
    // =========================================================================

    private array $_options = [];

    
    // Public Methods
    // =========================================================================

    public function getOptions(): array
    {
        return $this->_options;
    }

    public function setOptions(array $options): void
    {
        $this->_options = $options;
    }

    public function contains(mixed $value): bool
    {
        $value = (string)$value;

        foreach ($this as $selectedValue) {
            /** @var OptionData $selectedValue */
            if ($value === $selectedValue->value) {
                return true;
            }
        }

        return false;
    }
}
