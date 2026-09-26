<?php
namespace verbb\formie\fields\values;

use verbb\formie\Formie;
use verbb\formie\content\FieldStorageCodec;
use verbb\formie\elements\Submission;

use craft\base\ElementInterface;
use craft\helpers\Json;

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

    public function __construct(mixed $value = [], array $config = [])
    {
        parent::__construct($config);
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
        throw new \LogicException('Payment field values are immutable.');
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->parts);
    }

    public function toValueArray(): array
    {
        return $this->parts;
    }

    public function getAttributes(): array
    {
        return $this->parts;
    }


}
