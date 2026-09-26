<?php
namespace verbb\formie\fields\values;

use verbb\formie\helpers\ArrayHelper;

abstract class BaseFieldValue implements FieldValueInterface
{
    // Static Methods
    // =========================================================================


    public static function toClientValueFrom(mixed $value): mixed
    {
        if ($value instanceof static) {
            return $value->toClientValue();
        }

        if (is_array($value)) {
            return (new static($value))->toClientValue();
        }

        return $value;
    }


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, (string)$key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function __get(string $name): mixed
    {
        return $this->canResolvePath($name) ? $this->getPathValue($name) : null;
    }

    public function __isset(string $name): bool
    {
        return $this->canResolvePath($name) && $this->getPathValue($name) !== null;
    }

    public function __set(string $name, mixed $value): void
    {
        throw new \LogicException('Normalized field values are immutable. Construct a new value instead.');
    }

    public function toArray(): array
    {
        return $this->toValueArray();
    }

    abstract public function toValueArray(): array;

    public function toClientValue(): mixed
    {
        return $this->toValueArray();
    }

    public function toValueString(): string
    {
        return method_exists($this, '__toString') ? (string)$this : '';
    }

    public function canResolvePath(string $path): bool
    {
        return in_array($path, array_keys($this->toValueArray()), true);
    }

    public function getPathValue(string $path): mixed
    {
        return $this->canResolvePath($path) ? ArrayHelper::getValue($this->toValueArray(), $path) : null;
    }
}
