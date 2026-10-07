<?php
namespace verbb\formie\fields\values;

use verbb\formie\helpers\ArrayHelper;

use LogicException;

abstract class BaseFieldValue implements FieldValueInterface
{
    // Public Methods
    // =========================================================================

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
        throw new LogicException('Normalized field values are immutable. Construct a new value instead.');
    }

    abstract public function toArray(): array;

    public function canResolvePath(string $path): bool
    {
        return in_array($path, array_keys($this->toArray()), true);
    }

    public function getPathValue(string $path): mixed
    {
        return $this->canResolvePath($path) ? ArrayHelper::getValue($this->toArray(), $path) : null;
    }
}
