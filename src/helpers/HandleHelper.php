<?php
namespace verbb\formie\helpers;

use Craft;
use craft\helpers\FileHelper as CraftFileHelper;
use craft\helpers\StringHelper;

use Throwable;

class HandleHelper
{
    // Public Methods
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

    public static function getMaxFormHandle()
    {
        // The max length for the database engine, `fmc(d)_`, but also factor in duplicate suffixes (_XX)
        $maxHandleLength = Craft::$app->getDb()->getSchema()->maxObjectNameLength;
        $maxHandleLength -= 5;
        $maxHandleLength -= 3;

        return $maxHandleLength;
    }

    public static function getMaxFieldHandle()
    {
        // The max length for the database engine, `field_`, and the suffix for fields (10 chars extra to be safe)
        $maxHandleLength = Craft::$app->getDb()->getSchema()->maxObjectNameLength;
        $maxHandleLength -= strlen(Craft::$app->getContent()->fieldColumnPrefix);
        $maxHandleLength -= 8;

        // MySQL 8 struggles with 53+ column names https://github.com/verbb/formie/issues/1219
        $maxHandleLength -= 12;

        return $maxHandleLength;
    }
}
