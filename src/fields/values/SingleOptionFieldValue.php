<?php
namespace verbb\formie\fields\values;

use verbb\formie\helpers\ArrayHelper;

class SingleOptionFieldValue implements FieldValueInterface
{
    // Static Methods
    // =========================================================================


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

    public readonly ?string $label;
    public readonly ?string $value;
    public readonly bool $selected;
    public readonly bool $valid;
    private array $_options = [];


    // Public Methods
    // =========================================================================

    public function __construct(?string $label = null, ?string $value = null, bool $selected = false, bool $valid = true, array $options = [])
    {
        $this->label = $label;
        $this->value = $value;
        $this->selected = $selected;
        $this->valid = $valid;
        $this->_options = $options;
    }

    public function getOptions(): array
    {
        return $this->_options;
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
        return in_array($path, ['label', 'value', 'selected', 'valid'], true);
    }

    public function isEmpty(): bool
    {
        return $this->value === null || $this->value === '';
    }

    public function getDisplayLabel(): string
    {
        $label = trim((string)($this->label ?? ''));

        if ($label !== '') {
            return $label;
        }

        return (string)($this->value ?? '');
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }
}
