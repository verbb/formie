<?php
namespace verbb\formie\fields\values;

use verbb\formie\helpers\ArrayHelper;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

class MultiOptionFieldValue implements FieldValueInterface, IteratorAggregate, Countable
{
    // Static Methods
    // =========================================================================

    public static function capabilityTypes(): array
    {
        return ['string', 'array'];
    }

    public static function toClientValueFrom(mixed $value): mixed
    {
        if ($value instanceof self) {
            return $value->toClientValue();
        }

        if (!is_array($value)) {
            return $value;
        }

        return array_values(array_filter(array_map(static function(mixed $item): mixed {
            if ($item instanceof OptionValue) {
                return $item->value;
            }

            if (is_array($item) && array_key_exists('value', $item)) {
                return $item['value'];
            }

            return $item;
        }, $value), static fn(mixed $item): bool => $item !== null && $item !== ''));
    }


    // Properties
    // =========================================================================

    private array $_selectedOptions = [];
    private array $_options = [];


    // Public Methods
    // =========================================================================

    public function __construct(array $options = [])
    {
        $this->_selectedOptions = $options;
    }

    public function getOptions(): array
    {
        return $this->_options;
    }

    public function setOptions(array $options): void
    {
        $this->_options = $options;
    }

    public function all(): array
    {
        return $this->_selectedOptions;
    }

    public function values(): array
    {
        return array_map(static fn(OptionValue $option) => (string)$option->value, $this->_selectedOptions);
    }

    public function labels(): array
    {
        return array_map(static fn(OptionValue $option) => $option->getDisplayLabel(), $this->_selectedOptions);
    }

    public function getPathValue(string $path): mixed
    {
        if ($path === '') {
            return $this;
        }

        if (!$this->canResolvePath($path)) {
            return null;
        }

        if (ctype_digit($path)) {
            $option = $this->_selectedOptions[(int)$path] ?? null;

            return $option?->value;
        }

        return ArrayHelper::getValue($this->toValueArray(), $path);
    }

    public function toValueArray(): array
    {
        return array_map(static fn(OptionValue $option) => $option->toArray(), $this->_selectedOptions);
    }

    public function toClientValue(): mixed
    {
        return $this->values();
    }

    public function toValueString(): string
    {
        return (string)$this;
    }

    public function canResolvePath(string $path): bool
    {
        return $this->_canResolveIndexedPath($path);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->_selectedOptions);
    }

    public function count(): int
    {
        return count($this->_selectedOptions);
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function contains(mixed $value): bool
    {
        $value = (string)$value;

        foreach ($this->_selectedOptions as $selectedValue) {
            /** @var OptionValue $selectedValue */
            if ($value === $selectedValue->value) {
                return true;
            }
        }

        return false;
    }

    public function __toString(): string
    {
        return implode(', ', array_map(static fn(OptionValue $option) => (string)$option->value, $this->_selectedOptions));
    }


    // Private Methods
    // =========================================================================

    private function _canResolveIndexedPath(string $path): bool
    {
        $firstSegment = explode('.', $path)[0] ?? '';

        return $firstSegment !== '' && ctype_digit($firstSegment);
    }
}
