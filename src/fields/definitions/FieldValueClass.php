<?php
namespace verbb\formie\fields\definitions;

use verbb\formie\fields\values\FieldValueInterface;

use yii\base\BaseObject;

/**
 * Captures the optional value-class metadata that powers capability checks and
 * client payload serialization. It does not force the normalized PHP value for a
 * field to always be an instance of that class.
 */
class FieldValueClass extends BaseObject
{
    // Static Methods
    // =========================================================================

    public static function make(?string $className = null): self
    {
        return new self([
            'className' => $className,
        ]);
    }


    // Properties
    // =========================================================================

    public ?string $className = null;


    // Public Methods
    // =========================================================================

    public function withClassName(?string $className): self
    {
        $this->className = $className;

        return $this;
    }

    public function serializeClientValue(mixed $value): mixed
    {
        if ($this->className && method_exists($this->className, 'toClientValueFrom')) {
            $value = $this->className::toClientValueFrom($value);
        }

        return $this->serializeNestedClientValue($value);
    }

    public function capabilityTypes(): array
    {
        if (!$this->className || !is_subclass_of($this->className, FieldValueInterface::class)) {
            return [];
        }

        return array_values(array_unique(array_filter($this->className::capabilityTypes())));
    }

    public function supportsCapability(string $capabilityType): bool
    {
        return in_array($capabilityType, $this->capabilityTypes(), true);
    }

    public function toArray(): array
    {
        return [
            'class' => $this->className,
        ];
    }


    // Private Methods
    // =========================================================================

    private function serializeNestedClientValue(mixed $value): mixed
    {
        if ($value instanceof FieldValueInterface) {
            return $this->serializeNestedClientValue($value->toClientValue());
        }

        if (is_array($value)) {
            return array_map(fn(mixed $item): mixed => $this->serializeNestedClientValue($item), $value);
        }

        return $value;
    }
}
