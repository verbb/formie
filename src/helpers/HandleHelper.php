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
}
