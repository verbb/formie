<?php
namespace verbb\formie\fields\values;

use craft\helpers\DateTimeHelper;

use DateTime;
use DateTimeInterface;

class DateFieldValue extends BaseFieldValue
{
    use DateDisplaySettingsTrait;
    // Static Methods
    // =========================================================================

    public static function capabilityTypes(): array
    {
        return ['string', 'date', 'datetime'];
    }

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
            $value = $value->toValueString();
        } else if (is_object($value)) {
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

    public static function fromDateTime(\DateTimeInterface $dateTime): array
    {
        return [
            'year' => $dateTime->format('Y'),
            'month' => $dateTime->format('n'),
            'day' => $dateTime->format('j'),
            'hour' => $dateTime->format('G'),
            'minute' => $dateTime->format('i'),
            'second' => $dateTime->format('s'),
            'ampm' => strtoupper($dateTime->format('A')),
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

            $normalized[$partKey] = (string)(int)$value;
        }

        return $normalized;
    }

    public static function partsToString(array $parts): string
    {
        return self::formatPartsWithSettings(
            $parts,
            'Y-m-d',
            'H:i:s',
            self::_hasDateParts($parts),
            self::_hasTimeParts($parts),
        );
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

        $year = $hasDate ? (int)$parts['year'] : 1970;
        $month = $hasDate ? (int)$parts['month'] : 1;
        $day = $hasDate ? (int)$parts['day'] : 1;
        $hour = isset($parts['hour']) && $parts['hour'] !== '' ? (int)$parts['hour'] : 0;
        $minute = isset($parts['minute']) && $parts['minute'] !== '' ? (int)$parts['minute'] : 0;
        $second = isset($parts['second']) && $parts['second'] !== '' ? (int)$parts['second'] : 0;

        try {
            $dateTime = new DateTime('now', new \DateTimeZone('UTC'));
            $dateTime->setDate($year, $month, $day);
            $dateTime->setTime($hour, $minute, $second);

            return $dateTime;
        } catch (\Throwable) {
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

    private const PART_KEYS = ['year', 'month', 'day', 'hour', 'minute', 'second', 'ampm'];

    public static function partKeys(): array
    {
        return self::PART_KEYS;
    }

    
    // Properties
    // =========================================================================

    public array $parts = [];
    

    // Public Methods
    // =========================================================================

    public function __construct(mixed $value = [], array $config = [])
    {
        parent::__construct($config);

        $this->setParts(self::parseParts($value));
    }

    public function __toString(): string
    {
        return $this->stringify();
    }

    public function isEmpty(): bool
    {
        return empty($this->parts);
    }

    public function toValueArray(): array
    {
        return array_merge([
            'parts' => $this->parts,
        ], $this->parts);
    }

    public function setParts(array $parts): void
    {
        $this->parts = self::normalizeParts($parts);
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
            return $this->formatDateForDisplay($this->parts);
        }

        if ($path === 'time') {
            return $this->formatTimeForDisplay($this->parts);
        }

        if (in_array($path, self::PART_KEYS, true)) {
            return $this->parts[$path] ?? null;
        }

        return parent::getPathValue($path);
    }

    public static function formatDateInputValue(array $parts, string $dateFormat = 'Y-m-d'): string
    {
        return self::formatDateWithSettings($parts, $dateFormat);
    }

    public static function formatTimeInputValue(array $parts, string $timeFormat = 'H:i:s'): string
    {
        return self::formatTimeWithSettings($parts, $timeFormat);
    }


    // Private Methods
    // =========================================================================

    private function stringify(): string
    {
        return $this->formatPartsForDisplay($this->parts);
    }
}
