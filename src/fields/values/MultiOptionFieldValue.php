<?php
namespace verbb\formie\fields\values;

use verbb\formie\helpers\ArrayHelper;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

class MultiOptionFieldValue implements FieldValueInterface, IteratorAggregate, Countable
{
    // Properties
    // =========================================================================

    private array $_selectedOptions = [];
    private array $_options = [];


    // Public Methods
    // =========================================================================

    public function __construct(array $options = [], array $catalogue = [])
    {
        $this->_selectedOptions = $options;
        $this->_options = $catalogue;
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

        return ArrayHelper::getValue($this->toArray(), $path);
    }

    public function toArray(): array
    {
        return array_map(static fn(OptionValue $option) => $option->toArray(), $this->_selectedOptions);
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

        return preg_match('/^\d+(?:\.(?:value|label|selected|valid))?$/D', $path) === 1;
    }
}
