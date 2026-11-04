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

    /**
     * Formie 3 could save more decimals than a currency has, such as a failed payment of 25.015 USD.
     * Converting those rounds half up. The original decimal text stays on the payment.
     */
    public static function fromHistoricalDecimal(string|int $amount, string $currency): self
    {
        $currency = strtoupper($currency);
        $digits = (new ISOCurrencies())->subunitFor(new Currency($currency));
        $value = trim((string)$amount);

        if (!self::hasExcessPrecision($value, $currency)) {
            return self::fromDecimal($value, $currency);
        }

        preg_match('/^([+-]?)([0-9]+)\.([0-9]+)$/D', $value, $parts);
        $minor = $parts[2] . substr($parts[3], 0, $digits);

        // These strings can be longer than an integer, so carry by hand.
        if ((int)$parts[3][$digits] >= 5) {
            $index = strlen($minor) - 1;

            while ($index >= 0 && $minor[$index] === '9') {
                $minor[$index--] = '0';
            }

            $minor = $index < 0 ? '1' . $minor : substr_replace($minor, (string)((int)$minor[$index] + 1), $index, 1);
        }

        $minor = ltrim($minor, '0') ?: '0';

        return new self($parts[1] === '-' && $minor !== '0' ? '-' . $minor : $minor, $currency, $digits);
    }

    public static function hasExcessPrecision(string|int $amount, string $currency): bool
    {
        $digits = (new ISOCurrencies())->subunitFor(new Currency(strtoupper($currency)));

        if (!preg_match('/^[+-]?[0-9]+\.([0-9]+)$/D', trim((string)$amount), $parts)) {
            return false;
        }

        return strlen($parts[1]) > $digits && trim(substr($parts[1], $digits), '0') !== '';
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
