<?php
namespace verbb\formie\fields\values;

trait DateDisplaySettingsTrait
{
    public string $displayDateFormat = 'Y-m-d';
    public string $displayTimeFormat = 'H:i:s';
    public bool $displayIncludeDate = true;
    public bool $displayIncludeTime = true;

    public function applyDisplaySettings(
        string $dateFormat,
        string $timeFormat,
        bool $includeDate,
        bool $includeTime,
    ): void {
        $this->displayDateFormat = $dateFormat;
        $this->displayTimeFormat = $timeFormat;
        $this->displayIncludeDate = $includeDate;
        $this->displayIncludeTime = $includeTime;
    }

    public function formatDateForDisplay(array $parts): string
    {
        return DateFieldValue::formatDateWithSettings($parts, $this->displayDateFormat);
    }

    public function formatTimeForDisplay(array $parts): string
    {
        return DateFieldValue::formatTimeWithSettings($parts, $this->displayTimeFormat);
    }

    public function formatPartsForDisplay(array $parts): string
    {
        $includeDate = $this->displayIncludeDate && self::_hasDateParts($parts);
        $includeTime = $this->displayIncludeTime && self::_hasTimeParts($parts);

        return DateFieldValue::formatPartsWithSettings(
            $parts,
            $this->displayDateFormat,
            $this->displayTimeFormat,
            $includeDate,
            $includeTime,
        );
    }

    protected static function _hasDateParts(array $parts): bool
    {
        return isset($parts['year'], $parts['month'], $parts['day'])
            && $parts['year'] !== ''
            && $parts['month'] !== ''
            && $parts['day'] !== '';
    }

    protected static function _hasTimeParts(array $parts): bool
    {
        return (bool)array_intersect(array_keys($parts), ['hour', 'minute', 'second', 'ampm']);
    }
}
