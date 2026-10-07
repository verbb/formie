<?php
namespace verbb\formie\fields\values;

use verbb\formie\content\FieldStorageCodec;

use craft\helpers\DateTimeHelper;
use craft\helpers\Json;

use yii\base\UnknownPropertyException;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Throwable;

class DateFieldValue extends BaseFieldValue
{
    // Static Methods
    // =========================================================================

    /**
     * Convert a mixed value (DateTime, FieldValueInterface, object, string, numeric) to a DateTime instance.
     * Uses Craft's DateTimeHelper for parsing; does not interpret numeric values as milliseconds.
     */
    public static function toDateTime(mixed $value): ?DateTime
    {
        if ($value instanceof DateTime) {
            return clone $value;
        }

        if ($value instanceof DateTimeInterface && !($value instanceof DateTime)) {
            return DateTime::createFromInterface($value);
        }

        if ($value instanceof FieldValueInterface) {
            $value = (string)$value;
        } elseif (is_object($value)) {
            if (method_exists($value, '__toString')) {
                $value = (string)$value;
            } else {
                return null;
            }
        }

        if ($value === null || $value === '') {
            return null;
        }

        $date = DateTimeHelper::toDateTime($value);

        return ($date instanceof DateTime) ? $date : null;
    }

    public static function toDateString(mixed $value): ?string
    {
        $date = self::toDateTime($value);

        return $date ? $date->format('Y-m-d') : null;
    }

    public static function toDateTimeString(mixed $value): ?string
    {
        $date = self::toDateTime($value);

        return $date ? $date->format('Y-m-d H:i:s') : null;
    }

    public static function fromDateTime(DateTimeInterface $dateTime): array
    {
        return [
            'year' => $dateTime->format('Y'),
            'month' => $dateTime->format('n'),
            'day' => $dateTime->format('j'),
            'hour' => $dateTime->format('G'),
            'minute' => $dateTime->format('i'),
            'second' => $dateTime->format('s'),
            'ampm' => strtoupper($dateTime->format('A')),
            'timezone' => $dateTime->getTimezone()->getName(),
        ];
    }

    public static function parseParts(mixed $value): array
    {
        if ($value instanceof self) {
            return $value->getParts();
        }

        if ($value instanceof DateTimeInterface) {
            return self::fromDateTime($value);
        }

        if (is_array($value)) {
            if (isset($value['parts']) && is_array($value['parts'])) {
                return self::normalizeParts($value['parts']);
            }

            if (array_intersect(array_keys($value), self::PART_KEYS)) {
                return self::normalizeParts($value);
            }

            $parts = [];
            $datetimePart = trim((string)($value['datetime'] ?? ''));
            $datePart = trim((string)($value['date'] ?? ''));
            $timePart = trim((string)($value['time'] ?? ''));

            if ($datetimePart !== '') {
                return self::parseParts($datetimePart);
            }

            if ($datePart !== '') {
                $parts = array_merge($parts, self::_parseDatePart($datePart));
            }

            if ($timePart !== '') {
                $parts = array_merge($parts, self::_parseTimePart($timePart));
            }

            return self::normalizeParts($parts);
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            $value = (string)$value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $parsed = date_parse(trim($value));

        if (($parsed['error_count'] ?? 0) > 0) {
            return [];
        }

        $parts = [];

        if (($parsed['year'] ?? false) && ($parsed['month'] ?? false) && ($parsed['day'] ?? false)) {
            $parts['year'] = (string)$parsed['year'];
            $parts['month'] = (string)$parsed['month'];
            $parts['day'] = (string)$parsed['day'];
        }

        if (($parsed['hour'] ?? null) !== null) {
            $parts['hour'] = (string)$parsed['hour'];
        }

        if (($parsed['minute'] ?? null) !== null) {
            $parts['minute'] = (string)$parsed['minute'];
        }

        if (($parsed['second'] ?? null) !== null) {
            $parts['second'] = (string)$parsed['second'];
        }

        if (($parsed['meridian'] ?? null) !== null) {
            $parts['ampm'] = strtoupper((string)$parsed['meridian']);
        }

        if (($parsed['is_localtime'] ?? false) && ($parsed['zone_type'] ?? null) === 1) {
            $offset = (int)$parsed['zone'];
            $parts['timezone'] = sprintf('%s%02d:%02d', $offset < 0 ? '-' : '+', intdiv(abs($offset), 3600), intdiv(abs($offset) % 3600, 60));
        } elseif (isset($parsed['tz_id'])) {
            $parts['timezone'] = $parsed['tz_id'];
        }

        return self::normalizeParts($parts);
    }

    public static function normalizeParts(array $parts): array
    {
        $normalized = [];

        foreach (self::PART_KEYS as $partKey) {
            $value = $parts[$partKey] ?? null;

            if ($value === null) {
                continue;
            }

            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value === '') {
                continue;
            }

            if ($partKey === 'ampm') {
                $normalized[$partKey] = strtoupper((string)$value);
                continue;
            }

            $normalized[$partKey] = is_scalar($value) ? trim((string)$value) : Json::encode($value);

            if ($partKey !== 'timezone' && ctype_digit($normalized[$partKey])) {
                $normalized[$partKey] = ltrim($normalized[$partKey], '0') ?: '0';
            }
        }

        return $normalized;
    }

    public static function partsToString(array $parts): string
    {
        return self::formatPartsWithSettings(
            $parts,
            'Y-m-d',
            'H:i:s',
            self::hasCompleteDateParts($parts),
            (bool)array_intersect(array_keys($parts), ['hour', 'minute', 'second']),
        );
    }

    public static function hasCompleteDateParts(array $parts): bool
    {
        foreach (['year', 'month', 'day'] as $partKey) {
            if (!isset($parts[$partKey]) || $parts[$partKey] === '') {
                return false;
            }
        }

        return true;
    }

    public static function isValidCalendarDate(array $parts): bool
    {
        if (!self::hasCompleteDateParts($parts)) {
            return true;
        }

        return checkdate((int)$parts['month'], (int)$parts['day'], (int)$parts['year']);
    }

    public static function partsToDateTime(array $parts): ?DateTime
    {
        $hasDate = isset($parts['year'], $parts['month'], $parts['day'])
            && $parts['year'] !== ''
            && $parts['month'] !== ''
            && $parts['day'] !== '';
        $hasTime = (bool)array_intersect(array_keys($parts), ['hour', 'minute', 'second', 'ampm']);

        if (!$hasDate && !$hasTime) {
            return null;
        }

        if ($hasDate && !self::isValidCalendarDate($parts)) {
            return null;
        }

        foreach ($parts as $key => $part) {
            if (!in_array($key, ['ampm', 'timezone'], true) && !ctype_digit((string)$part)) {
                return null;
            }
        }

        if ((isset($parts['hour']) && (int)$parts['hour'] > 23) || (isset($parts['minute']) && (int)$parts['minute'] > 59) || (isset($parts['second']) && (int)$parts['second'] > 59)) {
            return null;
        }

        $year = $hasDate ? (int)$parts['year'] : 1970;
        $month = $hasDate ? (int)$parts['month'] : 1;
        $day = $hasDate ? (int)$parts['day'] : 1;
        $hour = isset($parts['hour']) && $parts['hour'] !== '' ? (int)$parts['hour'] : 0;
        $minute = isset($parts['minute']) && $parts['minute'] !== '' ? (int)$parts['minute'] : 0;
        $second = isset($parts['second']) && $parts['second'] !== '' ? (int)$parts['second'] : 0;

        if (isset($parts['ampm'])) {
            if (!in_array($parts['ampm'], ['AM', 'PM'], true)) {
                return null;
            }

            if ($parts['ampm'] === 'AM' && $hour === 12) {
                $hour = 0;
            } elseif ($parts['ampm'] === 'PM' && $hour < 12) {
                $hour += 12;
            }
        }

        try {
            $dateTime = new DateTime('now', new DateTimeZone($parts['timezone'] ?? 'UTC'));
            $dateTime->setDate($year, $month, $day);
            $dateTime->setTime($hour, $minute, $second);

            return $dateTime;
        } catch (Throwable) {
            return null;
        }
    }

    public static function formatDateWithSettings(array $parts, string $dateFormat): string
    {
        $dateTime = self::partsToDateTime($parts);

        if (!$dateTime instanceof DateTime) {
            return '';
        }

        return $dateTime->format($dateFormat);
    }

    public static function formatTimeWithSettings(array $parts, string $timeFormat): string
    {
        if (!array_intersect(array_keys($parts), ['hour', 'minute', 'second', 'ampm'])) {
            return '';
        }

        $dateTime = self::partsToDateTime($parts);

        if (!$dateTime instanceof DateTime) {
            return '';
        }

        return $dateTime->format($timeFormat);
    }

    public static function formatPartsWithSettings(
        array $parts,
        string $dateFormat,
        string $timeFormat,
        bool $includeDate = true,
        bool $includeTime = true,
    ): string {
        $segments = [];

        if ($includeDate) {
            $dateValue = self::formatDateWithSettings($parts, $dateFormat);

            if ($dateValue !== '') {
                $segments[] = $dateValue;
            }
        }

        if ($includeTime) {
            $timeValue = self::formatTimeWithSettings($parts, $timeFormat);

            if ($timeValue !== '') {
                $segments[] = $timeValue;
            }
        }

        return implode(' ', $segments);
    }

    public static function partKeys(): array
    {
        return self::PART_KEYS;
    }

    public static function formatDateInputValue(array $parts, string $dateFormat = 'Y-m-d'): string
    {
        return self::formatDateWithSettings($parts, $dateFormat);
    }

    public static function formatTimeInputValue(array $parts, string $timeFormat = 'H:i:s'): string
    {
        return self::formatTimeWithSettings($parts, $timeFormat);
    }

    private static function _parseDatePart(string $value): array
    {
        $parsed = date_parse($value);

        return [
            'year' => ($parsed['year'] ?? null) ? (string)$parsed['year'] : null,
            'month' => ($parsed['month'] ?? null) ? (string)$parsed['month'] : null,
            'day' => ($parsed['day'] ?? null) ? (string)$parsed['day'] : null,
        ];
    }

    private static function _parseTimePart(string $value): array
    {
        $parsed = date_parse($value);

        if (($parsed['error_count'] ?? 0) > 0) {
            return [];
        }

        $parts = [];

        if (($parsed['hour'] ?? null) !== null) {
            $parts['hour'] = (string)$parsed['hour'];
        }

        if (($parsed['minute'] ?? null) !== null) {
            $parts['minute'] = (string)$parsed['minute'];
        }

        if (($parsed['second'] ?? null) !== null) {
            $parts['second'] = (string)$parsed['second'];
        }

        if (($parsed['meridian'] ?? null) !== null) {
            $parts['ampm'] = strtoupper((string)$parsed['meridian']);
        }

        return $parts;
    }


    // Constants
    // =========================================================================

    private const PART_KEYS = ['year', 'month', 'day', 'hour', 'minute', 'second', 'ampm', 'timezone'];


    // Properties
    // =========================================================================

    protected array $parts = [];
    protected mixed $rawInput = null;


    // Public Methods
    // =========================================================================

    public function __construct(mixed $value = [])
    {
        if ($value instanceof self) {
            $value = $value->toArray();
        }
        $this->parts = self::parseParts($value);
        $this->rawInput = is_array($value) ? ($value['_input'] ?? null) : null;

        if ($this->rawInput === null && $this->parts === [] && $value !== null && $value !== '' && $value !== []) {
            $nonEmpty = is_array($value) ? array_filter($value, static fn($part) => $part !== null && $part !== '') : $value;
            $this->rawInput = $nonEmpty ? $value : null;
        }
        FieldStorageCodec::assertSafe($this->rawInput);
    }

    public function getRawInput(): mixed
    {
        return $this->rawInput;
    }

    public function __toString(): string
    {
        return $this->_stringify();
    }

    /**
     * Virtual date/time/part keys for ArrayHelper and Twig-style property access.
     * Stored state lives in `$parts`; `date`/`time` are display projections only.
     */
    public function __isset(string $name): bool
    {
        return $this->canResolvePath($name);
    }

    public function __get(string $name): mixed
    {
        if (!$this->canResolvePath($name)) {
            throw new UnknownPropertyException('Getting unknown property: ' . static::class . '::' . $name);
        }

        return $this->getPathValue($name);
    }

    public function isEmpty(): bool
    {
        return empty($this->parts) && $this->rawInput === null;
    }

    public function toArray(): array
    {
        return $this->rawInput === null ? $this->parts : $this->parts + ['_input' => $this->rawInput];
    }

    public function isValid(): bool
    {
        if ($this->rawInput !== null) {
            return false;
        }

        foreach ($this->parts as $key => $part) {
            if (!in_array($key, ['ampm', 'timezone'], true) && !ctype_digit((string)$part)) {
                return false;
            }
        }
        return !self::hasCompleteDateParts($this->parts) && !isset($this->parts['hour']) || self::partsToDateTime($this->parts) !== null;
    }

    public function getParts(): array
    {
        return $this->parts;
    }

    public function getPart(string $key): ?string
    {
        return $this->parts[$key] ?? null;
    }

    public function canResolvePath(string $path): bool
    {
        return $path === 'date'
            || $path === 'time'
            || in_array($path, self::PART_KEYS, true);
    }

    public function getPathValue(string $path): mixed
    {
        if ($path === 'date') {
            return self::formatDateWithSettings($this->parts, 'Y-m-d');
        }

        if ($path === 'time') {
            return self::formatTimeWithSettings($this->parts, 'H:i:s');
        }

        if (in_array($path, self::PART_KEYS, true)) {
            return $this->parts[$path] ?? null;
        }

        return parent::getPathValue($path);
    }


    // Private Methods
    // =========================================================================

    private function _stringify(): string
    {
        if ($this->rawInput !== null) {
            return is_string($this->rawInput) ? $this->rawInput : '';
        }
        return self::partsToString($this->parts);
    }
}
