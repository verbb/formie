<?php
namespace verbb\formie\fields\values;

use verbb\formie\helpers\ArrayHelper;

abstract class BaseFieldValue implements FieldValueInterface
{
    // Static Methods
    // =========================================================================

    public static function capabilityTypes(): array
    {
        return [];
    }

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

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function toValueArray(): array
    {
        return $this->toArray();
    }

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
        return $path !== '';
    }

    public function getPathValue(string $path): mixed
    {
        return $this->canResolvePath($path) ? ArrayHelper::getValue($this->toValueArray(), $path) : $this;
    }
}
