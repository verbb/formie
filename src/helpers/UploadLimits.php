<?php
namespace verbb\formie\helpers;

use Craft;
use craft\helpers\ConfigHelper;

final class UploadLimits
{
    // Static Methods
    // =========================================================================

    public static function maxFileBytes(): int
    {
        $bytes = ConfigHelper::sizeInBytes(Craft::$app->getConfig()->getGeneral()->maxUploadFileSize);

        return $bytes > 0 ? $bytes : 16777216;
    }
}
