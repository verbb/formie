<?php
namespace verbb\formie\fields\values;

class OptionValue
{
    // Properties
    // =========================================================================

    public ?string $label = null;
    public ?string $value = null;
    public bool $selected = false;
    public bool $valid = true;


    // Public Methods
    // =========================================================================

    public function __construct(?string $label = null, ?string $value = null, bool $selected = false, bool $valid = true)
    {
        $this->label = $label;
        $this->value = $value;
        $this->selected = $selected;
        $this->valid = $valid;
    }

    public function getOptions(): array
    {
        return [];
    }

    public function getDisplayLabel(): string
    {
        $label = trim((string)($this->label ?? ''));

        if ($label !== '') {
            return $label;
        }

        return (string)($this->value ?? '');
    }

    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'value' => $this->value,
            'selected' => $this->selected,
            'valid' => $this->valid,
        ];
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }
}
