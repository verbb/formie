<?php
namespace verbb\formie\models;

use InvalidArgumentException;

use Money\Currencies\ISOCurrencies;
use Money\Currency;

final class PaymentMoney
{
    // Static Methods
    // =========================================================================

    public static function fromDecimal(string|int $amount, string $currency): self
    {
        $currency = strtoupper($currency);
        $digits = (new ISOCurrencies())->subunitFor(new Currency($currency));
        $value = trim((string)$amount);

        if (!preg_match('/^([+-]?)([0-9]+)(?:\.([0-9]+))?$/D', $value, $parts)) {
            throw new InvalidArgumentException('Invalid decimal payment amount.');
        }
        $fraction = $parts[3] ?? '';

        if (strlen($fraction) > $digits && trim(substr($fraction, $digits), '0') !== '') {
            throw new InvalidArgumentException('Payment amount exceeds the currency precision.');
        }
        $minor = ltrim($parts[2] . str_pad(substr($fraction, 0, $digits), $digits, '0'), '0') ?: '0';

        if ($parts[1] === '-' && $minor !== '0') {
            $minor = '-' . $minor;
        }
        return new self($minor, $currency, $digits);
    }

    public static function fromMinor(string|int $amount, string $currency): self
    {
        $currency = strtoupper($currency);

        if (!preg_match('/^-?[0-9]+$/D', (string)$amount)) {
            throw new InvalidArgumentException('Invalid minor-unit amount.');
        }
        $negative = str_starts_with((string)$amount, '-');
        $minor = ltrim(ltrim((string)$amount, '-'), '0') ?: '0';
        return new self(($negative && $minor !== '0' ? '-' : '') . $minor, $currency, (new ISOCurrencies())->subunitFor(new Currency($currency)));
    }


    // Properties
    // =========================================================================

    public readonly string $minor;
    public readonly string $currency;
    public readonly int $digits;


    // Public Methods
    // =========================================================================

    public function decimal(): string
    {
        $negative = str_starts_with($this->minor, '-');
        $value = str_pad(ltrim($this->minor, '-'), $this->digits + 1, '0', STR_PAD_LEFT);
        return ($negative ? '-' : '') . ($this->digits ? substr($value, 0, -$this->digits) . '.' . substr($value, -$this->digits) : $value);
    }

    public function integer(): int
    {
        $value = (int)$this->minor;

        if ((string)$value !== $this->minor) {
            throw new InvalidArgumentException('Provider amount exceeds the supported integer range.');
        }
        return $value;
    }

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency && $this->minor === $other->minor;
    }

    // Private Methods
    // =========================================================================

    private function __construct(string $minor, string $currency, int $digits)
    {
        $this->minor = $minor;
        $this->currency = $currency;
        $this->digits = $digits;
    }

}
