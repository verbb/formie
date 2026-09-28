<?php
namespace verbb\formie\fields\values;

use verbb\formie\helpers\ArrayHelper;

class RecipientsFieldValue implements FieldValueInterface
{
    // Properties
    // =========================================================================

    private ?string $_displayType = null;
    private mixed $_rawValue = null;
    private ?string $_label = null;
    private bool $_valid = true;
    private array $_selectedOptions = [];
    private array $_options = [];


    // Public Methods
    // =========================================================================

    public function __construct(
        ?string $displayType = null,
        mixed $rawValue = null,
        ?string $label = null,
        bool $valid = true,
        array $selectedOptions = [],
        array $options = [],
    ) {
        $this->_displayType = $displayType;
        $this->_rawValue = $rawValue;
        $this->_label = $label;
        $this->_valid = $valid;
        $this->_selectedOptions = $selectedOptions;
        $this->_options = $options;
    }

    public function displayType(): ?string
    {
        return $this->_displayType;
    }

    public function rawValue(): mixed
    {
        return $this->_rawValue;
    }

    public function label(): ?string
    {
        return $this->_label;
    }

    public function valid(): bool
    {
        return $this->_valid;
    }


    public function getOptions(): array
    {
        return $this->_options;
    }

    public function selectedOptions(): array
    {
        return $this->_selectedOptions;
    }

    public function values(): array
    {
        if ($this->_displayType === 'checkboxes') {
            return array_map(static fn(OptionValue $option) => (string)$option->value, $this->_selectedOptions);
        }

        if ($this->_rawValue === null || $this->_rawValue === '') {
            return [];
        }

        if (is_array($this->_rawValue)) {
            return array_map(static fn(mixed $item): string => (string)$item, $this->_rawValue);
        }

        return [(string)$this->_rawValue];
    }

    public function labels(): array
    {
        if ($this->_displayType === 'checkboxes') {
            return array_map(static fn(OptionValue $option) => (string)$option->label, $this->_selectedOptions);
        }

        return $this->_label ? [$this->_label] : [];
    }

    public function isEmpty(): bool
    {
        return $this->values() === [];
    }

    public function toArray(): array
    {
        if ($this->_displayType === 'checkboxes') {
            return array_map(static fn(OptionValue $option) => $option->toArray(), $this->_selectedOptions);
        }

        if ($this->_displayType === 'dropdown' || $this->_displayType === 'radio') {
            return [
                'label' => $this->_label,
                'value' => $this->_rawValue,
                'selected' => $this->_rawValue !== null && $this->_rawValue !== '',
                'valid' => $this->_valid,
            ];
        }

        return [
            'value' => $this->_rawValue,
        ];
    }

    public function __toString(): string
    {
        return implode(', ', $this->values());
    }

    public function canResolvePath(string $path): bool
    {
        return in_array($path, array_keys($this->toArray()), true);
    }

    public function getPathValue(string $path): mixed
    {
        if ($path === '') {
            return $this;
        }

        if (!$this->canResolvePath($path)) {
            return null;
        }

        return ArrayHelper::getValue($this->toArray(), $path);
    }
}
