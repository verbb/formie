<?php
namespace verbb\formie\fields\values;

use verbb\formie\helpers\ArrayHelper;

class SingleOptionFieldValue implements FieldValueInterface
{
    // Static Methods
    // =========================================================================

    public static function capabilityTypes(): array
    {
        return ['string'];
    }

    public static function toClientValueFrom(mixed $value): mixed
    {
        if ($value instanceof self || $value instanceof OptionValue) {
            return $value->value;
        }

        if (is_array($value) && array_key_exists('value', $value)) {
            return $value['value'];
        }

        return $value;
    }


    // Properties
    // =========================================================================

    public ?string $label = null;
    public ?string $value = null;
    public bool $selected = false;
    public bool $valid = true;
    private array $_options = [];


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
        return $this->_options;
    }

    public function setOptions(array $options): void
    {
        $this->_options = $options;
    }

    public function getPathValue(string $path): mixed
    {
        if ($path === '') {
            return $this;
        }

        if (!$this->canResolvePath($path)) {
            return null;
        }

        return ArrayHelper::getValue($this->toValueArray(), $path);
    }

    public function toValueArray(): array
    {
        return [
            'label' => $this->label,
            'value' => $this->value,
            'selected' => $this->selected,
            'valid' => $this->valid,
            'options' => array_map(static fn(OptionValue $option) => $option->toArray(), $this->_options),
        ];
    }

    public function toClientValue(): mixed
    {
        return $this->value;
    }

    public function toValueString(): string
    {
        return (string)$this;
    }

    public function canResolvePath(string $path): bool
    {
        return $path !== '';
    }

    public function isEmpty(): bool
    {
        return $this->value === null || $this->value === '';
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }
}
