<?php
namespace verbb\formie\helpers;

use verbb\formie\models\DateTime;
use craft\helpers\DateTimeHelper as CraftDateTimeHelper;

use DateTime as NativeDateTime;

class DateTimeHelper extends CraftDateTimeHelper
{
    public static function toDateTime(mixed $value, bool $assumeSystemTimeZone = false, bool $setToSystemTimeZone = true): DateTime|false
    {
        $dateTime = parent::toDateTime($value, $assumeSystemTimeZone, $setToSystemTimeZone);

        if ($dateTime instanceof NativeDateTime) {
            return new DateTime($dateTime->format('c'));
        }

        return $dateTime;
    }
}
