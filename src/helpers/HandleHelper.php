<?php
namespace verbb\formie\helpers;

use Craft;

class HandleHelper
{
    // Static Methods
    // =========================================================================

    public static function getUniqueHandle(array $handles, string $handle, int $suffix = 0, ?int $maxLength = null)
    {
        $newHandle = $handle;

        if ($suffix) {
            $newHandle = $handle . $suffix;
        }

        // If a max length is enforced and the handle (including any suffix) would exceed it,
        // truncate the base handle - not the suffix - so the result still fits and stays unique.
        if ($maxLength !== null && strlen($newHandle) > $maxLength) {
            $suffixLength = $suffix ? strlen((string)$suffix) : 0;
            $newHandle = substr($handle, 0, $maxLength - $suffixLength) . ($suffix ?: '');
        }

        if (in_array($newHandle, $handles)) {
            return self::getUniqueHandle($handles, $handle, $suffix + 1, $maxLength);
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
        // The max length for the database engine
        return Craft::$app->getDb()->getSchema()->maxObjectNameLength;
    }
}
