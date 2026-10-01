<?php
namespace verbb\formie\fields\values;

class DateRangeFieldValue extends BaseFieldValue
{
    // Static Methods
    // =========================================================================


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
        return $value instanceof self ? $value : new self($value);
    }


    // Properties
    // =========================================================================

    public readonly DateFieldValue $start;
    public readonly DateFieldValue $end;


    // Public Methods
    // =========================================================================

    public function __construct(mixed $value = [])
    {
        $value = is_array($value) ? $value : ['start' => $value];
        $this->start = new DateFieldValue($value['start'] ?? self::_parseFlatSideParts($value, 'start'));
        $this->end = new DateFieldValue($value['end'] ?? self::_parseFlatSideParts($value, 'end'));
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

        $start = (string)$this->start;
        $end = (string)$this->end;

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
        return $this->start->isEmpty() && $this->end->isEmpty();
    }

    public function toArray(): array
    {
        return ['start' => $this->start->toArray(), 'end' => $this->end->toArray()];
    }

    public function getStartParts(): array
    {
        return $this->start->getParts();
    }

    public function getEndParts(): array
    {
        return $this->end->getParts();
    }


    /**
     * Virtual start/end (and side-part) keys for ArrayHelper property access.
     * Same contract as DateFieldValue — range sides are projections, not stored props.
     */
    public function __isset(string $name): bool
    {
        return $this->canResolvePath($name);
    }

    public function __get(string $name): mixed
    {
        if (!$this->canResolvePath($name)) {
            throw new \yii\base\UnknownPropertyException('Getting unknown property: ' . static::class . '::' . $name);
        }

        return $this->getPathValue($name);
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
            return (string)$this->start;
        }

        if ($path === 'end') {
            return (string)$this->end;
        }

        $resolved = $this->_resolveSidePartKey($path);

        if ($resolved === null) {
            return parent::getPathValue($path);
        }

        [$side, $partKey] = $resolved;
        $parts = $side === 'start' ? $this->getStartParts() : $this->getEndParts();

        if ($partKey === 'date') {
            return DateFieldValue::formatDateWithSettings($parts, 'Y-m-d');
        }

        if ($partKey === 'time') {
            return DateFieldValue::formatTimeWithSettings($parts, 'H:i:s');
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
