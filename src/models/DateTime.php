<?php
namespace verbb\formie\models;

use verbb\formie\base\FieldValueInterface;

use DateTime as NativeDateTime;
use DateTimeZone as NativeDateTimeZone;

class DateTime extends NativeDateTime implements FieldValueInterface, \ArrayAccess
{
    // Properties
    // =========================================================================

    public const STORAGE_TZ = 'UTC';
    

    // Public Methods
    // =========================================================================

    public function __construct(string $datetime = 'now', ?NativeDateTimeZone $timezone = null)
    {
        // If no TZ provided, assume UTC
        $timezone ??= new NativeDateTimeZone(self::STORAGE_TZ);

        parent::__construct($datetime, $timezone);

        // Normalize internal TZ to canonical
        $this->setTimezone(new NativeDateTimeZone(self::STORAGE_TZ));
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'year' => (int)$this->format('Y'),
            'month' => (int)$this->format('m'),
            'day' => (int)$this->format('d'),
            'hour' => (int)$this->format('H'),
            'minute' => (int)$this->format('i'),
            'second' => (int)$this->format('s'),
            'ampm' => $this->format('A'),
            default  => null,
        };
    }

    public function offsetExists(mixed $offset): bool
    {
        return in_array($offset, [
            'year',
            'month',
            'day',
            'hour',
            'minute',
            'second',
            'ampm',
        ], true);
    }

    public function offsetGet(mixed $offset): mixed
    {
        // Delegate to __get() so logic stays in one place
        return $this->__get((string)$offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('DateTime values are read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('DateTime values are read-only.');
    }
}
