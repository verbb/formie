<?php
namespace verbb\formie\fields\data;

class SingleOptionFieldData extends OptionData
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
}
