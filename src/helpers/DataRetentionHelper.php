<?php
namespace verbb\formie\helpers;

use DateInterval;
use DateTime;
use DateTimeInterface;

class DataRetentionHelper
{
    public static function isActive(string $unit, mixed $value): bool
    {
        return $unit !== 'forever' && (int)$value > 0;
    }

    public static function subtractInterval(DateTimeInterface $from, string $unit, int $value): ?DateTime
    {
        if (!self::isActive($unit, $value)) {
            return null;
        }

        $intervalLookup = ['minutes' => 'MIN', 'hours' => 'H', 'days' => 'D', 'weeks' => 'W', 'months' => 'M', 'years' => 'Y'];
        $intervalValue = $intervalLookup[$unit] ?? '';

        if (!$intervalValue) {
            return null;
        }

        $durationValue = $value;

        if ($intervalValue === 'W') {
            $intervalValue = 'D';
            $durationValue *= 7;
        }

        $period = ($intervalValue === 'H' || $intervalValue === 'MIN') ? 'PT' : 'P';

        if ($intervalValue === 'MIN') {
            $intervalValue = 'M';
        }

        $date = DateTime::createFromInterface($from);
        $date->sub(new DateInterval("{$period}{$durationValue}{$intervalValue}"));

        return $date;
    }
}
