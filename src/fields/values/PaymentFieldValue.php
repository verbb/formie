<?php
namespace verbb\formie\fields\values;

use verbb\formie\Formie;
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

        if (is_object($value) && method_exists($value, 'toArray')) {
            $array = $value->toArray();

            return is_array($array) ? $array : [];
        }

        return [];
    }


    // Properties
    // =========================================================================

    public array $parts = [];

    private ?ElementInterface $_element = null;


    // Public Methods
    // =========================================================================

    public function __construct(mixed $value = [], array $config = [])
    {
        parent::__construct($config);
        $this->parts = self::parseParts($value);
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
        $this->parts[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->parts);
    }

    public function toValueArray(): array
    {
        return array_merge([
            'parts' => $this->parts,
        ], $this->parts);
    }

    public function getAttributes(): array
    {
        return $this->parts;
    }

    public function getElement(): ?ElementInterface
    {
        return $this->_element;
    }

    public function setElement(?ElementInterface $value): void
    {
        $this->_element = $value;
    }

    public function getPayment(): ?array
    {
        if ($submission = $this->getElement()) {
            if ($submission instanceof Submission) {
                if ($payments = Formie::$plugin->getPayments()->getSubmissionPayments($submission)) {
                    $lastPayment = $payments[count($payments) - 1];

                    return $lastPayment->toArray();
                }
            }
        }

        return null;
    }
}
