<?php
namespace verbb\formie\helpers;

use Craft;
use craft\base\LocalFsInterface;
use craft\elements\Asset;
use craft\helpers\Assets as CraftAssets;
use craft\helpers\FileHelper;

class Assets extends CraftAssets
{
    // Static Methods
    // =========================================================================

    public static function getFullAssetFilePath(Asset $asset): string
    {
        $volume = $asset->getVolume();
        $fs = $volume->getFs();

        if ($fs instanceof LocalFsInterface) {
            $path = sprintf('%s/%s/%s', rtrim($fs->getRootPath(), '/'), rtrim($volume->getSubpath(), '/'), $asset->getPath());

            return FileHelper::normalizePath($path);
        }

        return $asset->getCopyOfFile();
    }
}
