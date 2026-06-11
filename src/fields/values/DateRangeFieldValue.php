<?php
namespace verbb\formie\fields\values;

class DateRangeFieldValue extends BaseFieldValue
{
    use DateDisplaySettingsTrait;
    // Static Methods
    // =========================================================================

    public static function capabilityTypes(): array
    {
        return ['string', 'date', 'datetime'];
    }

    public static function partKeys(): array
    {
        return ['year', 'month', 'day', 'hour', 'minute', 'second', 'ampm'];
    }

    public static function sidePrefixes(): array
    {
        return ['start', 'end'];
    }

    public static function parseSideParts(mixed $value): array
    {
        if ($value instanceof DateFieldValue) {
            return $value->getParts();
        }

        if ($value instanceof \DateTimeInterface) {
            return DateFieldValue::fromDateTime($value);
        }

        return DateFieldValue::parseParts($value);
    }

    public static function fromMixed(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (!is_array($value)) {
            return new self();
        }

        if (isset($value['start']) || isset($value['end'])) {
            return new self([
                'start' => self::parseSideParts($value['start'] ?? []),
                'end' => self::parseSideParts($value['end'] ?? []),
            ]);
        }

        return new self([
            'start' => self::_parseFlatSideParts($value, 'start'),
            'end' => self::_parseFlatSideParts($value, 'end'),
        ]);
    }


    // Properties
    // =========================================================================

    public array $start = [];
    public array $end = [];


    // Public Methods
    // =========================================================================

    public function __construct(mixed $value = [], array $config = [])
    {
        if (is_array($value) && (isset($value['start']) || isset($value['end']))) {
            $this->setStartParts($value['start'] ?? []);
            $this->setEndParts($value['end'] ?? []);
            parent::__construct($config);

            return;
        }

        if (is_array($value) && $value !== []) {
            $parsed = self::fromMixed($value);
            $this->start = $parsed->start;
            $this->end = $parsed->end;
            parent::__construct($config);

            return;
        }

        parent::__construct($config);
    }

    public function __toString(): string
    {
        return $this->formatForDisplay();
    }

    public function formatForDisplay(): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        $start = $this->formatPartsForDisplay($this->start);
        $end = $this->formatPartsForDisplay($this->end);

        if ($start === '' && $end === '') {
            return '';
        }

        if ($start === '' || $end === '') {
            return trim("$start $end");
        }

        return "$start – $end";
    }

    public function isEmpty(): bool
    {
        return empty($this->start) && empty($this->end);
    }

    public function toValueArray(): array
    {
        $value = [
            'start' => $this->start,
            'end' => $this->end,
        ];

        foreach (self::sidePrefixes() as $side) {
            $parts = $side === 'start' ? $this->start : $this->end;

            foreach (self::partKeys() as $partKey) {
                if (array_key_exists($partKey, $parts)) {
                    $value[$side . ucfirst($partKey)] = $parts[$partKey];
                }
            }

            $value[$side . 'Date'] = $this->formatDateForDisplay($parts);
            $value[$side . 'Time'] = $this->formatTimeForDisplay($parts);
        }

        return $value;
    }

    public function getStartParts(): array
    {
        return $this->start;
    }

    public function getEndParts(): array
    {
        return $this->end;
    }

    public function setStartParts(mixed $parts): void
    {
        $this->start = DateFieldValue::normalizeParts(self::parseSideParts($parts));
    }

    public function setEndParts(mixed $parts): void
    {
        $this->end = DateFieldValue::normalizeParts(self::parseSideParts($parts));
    }

    public function canResolvePath(string $path): bool
    {
        if ($path === 'start' || $path === 'end') {
            return true;
        }

        return $this->_resolveSidePartKey($path) !== null;
    }

    public function getPathValue(string $path): mixed
    {
        if ($path === 'start') {
            return $this->formatPartsForDisplay($this->start);
        }

        if ($path === 'end') {
            return $this->formatPartsForDisplay($this->end);
        }

        $resolved = $this->_resolveSidePartKey($path);

        if ($resolved === null) {
            return parent::getPathValue($path);
        }

        [$side, $partKey] = $resolved;
        $parts = $side === 'start' ? $this->start : $this->end;

        if ($partKey === 'date') {
            return $this->formatDateForDisplay($parts);
        }

        if ($partKey === 'time') {
            return $this->formatTimeForDisplay($parts);
        }

        return $parts[$partKey] ?? null;
    }


    // Private Methods
    // =========================================================================

    private static function _parseFlatSideParts(array $value, string $side): array
    {
        $parts = [];

        foreach (self::partKeys() as $partKey) {
            $prefixedKey = $side . ucfirst($partKey);

            if (array_key_exists($prefixedKey, $value)) {
                $parts[$partKey] = $value[$prefixedKey];
            }
        }

        $datePart = trim((string)($value[$side . 'Date'] ?? ''));
        $timePart = trim((string)($value[$side . 'Time'] ?? ''));

        if ($datePart !== '' || $timePart !== '') {
            return DateFieldValue::parseParts([
                'date' => $datePart,
                'time' => $timePart,
            ]);
        }

        return DateFieldValue::normalizeParts($parts);
    }

    private function _resolveSidePartKey(string $path): ?array
    {
        foreach (self::sidePrefixes() as $side) {
            if (!str_starts_with($path, $side)) {
                continue;
            }

            $suffix = substr($path, strlen($side));

            if ($suffix === '') {
                return [$side, null];
            }

            $partKey = lcfirst($suffix);

            if (in_array($partKey, [...self::partKeys(), 'date', 'time'], true)) {
                return [$side, $partKey];
            }
        }

        return null;
    }
}
