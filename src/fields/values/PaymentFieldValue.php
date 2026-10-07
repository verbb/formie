<?php
namespace verbb\formie\fields\values;

use verbb\formie\content\FieldStorageCodec;

use craft\helpers\Json;

use LogicException;

class PaymentFieldValue extends BaseFieldValue
{
    // Static Methods
    // =========================================================================

    public static function parseParts(mixed $value): array
    {
        if ($value instanceof self) {
            return $value->parts;
        }

        if (is_array($value)) {
            if (isset($value['parts']) && is_array($value['parts'])) {
                return $value['parts'];
            }

            return $value;
        }


        return [];
    }


    // Properties
    // =========================================================================

    protected array $parts = [];


    // Public Methods
    // =========================================================================

    public function __construct(mixed $value = [])
    {
        $this->parts = FieldStorageCodec::assertSafe(self::parseParts($value));
    }

    public function __toString(): string
    {
        return Json::encode($this->parts);
    }

    public function isEmpty(): bool
    {
        return empty($this->parts);
    }

    public function __get(string $name): mixed
    {
        return $this->parts[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        throw new LogicException('Payment field values are immutable.');
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->parts);
    }

    public function toArray(): array
    {
        return $this->parts;
    }

    public function getAttributes(): array
    {
        return $this->parts;
    }
}
