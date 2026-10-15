<?php
namespace verbb\formie\fields\values;

use verbb\formie\helpers\ArrayHelper;

class RecipientsFieldValue implements FieldValueInterface
{
    // Properties
    // =========================================================================

    public readonly mixed $value;
    public readonly ?string $label;
    public readonly bool $valid;
    public readonly bool $multiple;

    private array $_selectedOptions = [];
    private array $_options = [];


    // Public Methods
    // =========================================================================

    public function __construct(
        mixed $value = null,
        ?string $label = null,
        bool $valid = true,
        array $selectedOptions = [],
        array $options = [],
        bool $multiple = false,
    ) {
        $this->value = $value;
        $this->label = $label;
        $this->valid = $valid;
        $this->multiple = $multiple;
        $this->_selectedOptions = $selectedOptions;
        $this->_options = $options;
    }

    public function getOptions(): array
    {
        return $this->_options;
    }

    public function all(): array
    {
        return $this->_selectedOptions;
    }

    public function values(): array
    {
        if ($this->multiple) {
            return array_map(static fn(OptionValue $option) => (string)$option->value, $this->_selectedOptions);
        }

        if ($this->value === null || $this->value === '') {
            return [];
        }

        if (is_array($this->value)) {
            return array_map(static fn(mixed $item): string => (string)$item, $this->value);
        }

        return [(string)$this->value];
    }

    public function labels(): array
    {
        if ($this->multiple) {
            return array_map(static fn(OptionValue $option) => (string)$option->label, $this->_selectedOptions);
        }

        return $this->label ? [$this->label] : [];
    }

    public function isEmpty(): bool
    {
        return $this->values() === [];
    }

    public function toArray(): array
    {
        if ($this->multiple) {
            return array_map(static fn(OptionValue $option) => $option->toArray(), $this->_selectedOptions);
        }

        return [
            'label' => $this->label,
            'value' => $this->value,
            'selected' => $this->value !== null && $this->value !== '',
            'valid' => $this->valid,
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
