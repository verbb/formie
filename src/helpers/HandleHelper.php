<?php
namespace verbb\formie\helpers;

use Craft;

class HandleHelper
{
    // Static Methods
    // =========================================================================

    public static function getUniqueHandle($handles, $handle, $suffix = 0)
    {
        $newHandle = $handle;

        if ($suffix) {
            $newHandle = $handle . $suffix;
        }

        if (in_array($newHandle, $handles)) {
            return self::getUniqueHandle($handles, $handle, $suffix + 1);
        }

        return $newHandle;
    }

    public static function getMaxFormHandle(): int
    {
        // The max length for the database engine, `fmc(d)_`, but also factor in duplicate suffixes (_XX)
        $maxHandleLength = Craft::$app->getDb()->getSchema()->maxObjectNameLength;
        $maxHandleLength -= 5;
        $maxHandleLength -= 3;

        return $maxHandleLength;
    }

    public static function getMaxFieldHandle(): int
    {
        // The max length for the database engine, `field_`, and the suffix for fields (10 chars extra to be safe)
        $maxHandleLength = Craft::$app->getDb()->getSchema()->maxObjectNameLength;
        $maxHandleLength -= strlen(Craft::$app->getContent()->fieldColumnPrefix);
        $maxHandleLength -= 10;

        return $maxHandleLength;
    }
}
