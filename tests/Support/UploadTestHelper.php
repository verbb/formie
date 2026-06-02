<?php

declare(strict_types=1);

namespace Tests\Support;

use Craft;
use craft\elements\Asset;
use craft\fs\Local;
use craft\helpers\Assets;
use craft\models\Volume;
use RuntimeException;
use verbb\formie\Formie;

final class UploadTestHelper
{
    public static function ensureUploadVolume(string $handle = 'formieTestUploads', string $name = 'Formie Test Uploads'): Volume
    {
        $fs = self::ensureLocalFilesystem();
        $volumes = Craft::$app->getVolumes();
        $volume = $volumes->getVolumeByHandle($handle);

        if ($volume) {
            if ($volume->fsHandle !== $fs->handle) {
                $volume->fsHandle = $fs->handle;

                if (!$volumes->saveVolume($volume)) {
                    throw new RuntimeException('Unable to update upload test volume: ' . json_encode($volume->getErrors()));
                }

                $volume = $volumes->getVolumeByHandle($handle) ?? $volume;
            }

            self::setDefaultUploadVolume($volume);
            return $volume;
        }

        $volume = new Volume([
            'handle' => $handle,
            'name' => $name,
            'fsHandle' => $fs->handle,
            'sortOrder' => 1,
        ]);

        if (!$volumes->saveVolume($volume)) {
            throw new RuntimeException('Unable to create upload test volume: ' . json_encode($volume->getErrors()));
        }

        $volume = $volumes->getVolumeByHandle($handle) ?? $volume;
        self::setDefaultUploadVolume($volume);

        return $volume;
    }

    public static function seedAsset(string $filename, string $contents, ?Volume $volume = null): Asset
    {
        $volume ??= self::ensureUploadVolume();
        $folder = Craft::$app->getAssets()->getRootFolderByVolumeId($volume->id);

        if (!$folder) {
            throw new RuntimeException("Unable to resolve root folder for upload test volume `{$volume->handle}`.");
        }

        $tempPath = Assets::tempFilePath($filename);
        file_put_contents($tempPath, $contents);

        $asset = new Asset();
        $asset->tempFilePath = $tempPath;
        $asset->filename = $filename;
        $asset->newFolderId = $folder->id;
        $asset->setVolumeId($volume->id);
        $asset->avoidFilenameConflicts = true;

        if (!Craft::$app->getElements()->saveElement($asset)) {
            throw new RuntimeException("Unable to create seeded asset `{$filename}`: " . json_encode($asset->getErrors()));
        }

        return $asset;
    }

    private static function ensureLocalFilesystem(string $handle = 'formieTestUploadsFs', string $name = 'Formie Test Uploads FS'): Local
    {
        $fsService = Craft::$app->getFs();
        $filesystem = $fsService->getFilesystemByHandle($handle);

        if ($filesystem instanceof Local) {
            return $filesystem;
        }

        $basePath = defined('CRAFT_BASE_PATH') ? CRAFT_BASE_PATH : getcwd();
        $uploadsPath = rtrim((string)dirname((string)$basePath), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'formie-plugin-test-uploads';

        if (!is_dir($uploadsPath) && !mkdir($uploadsPath, 0775, true) && !is_dir($uploadsPath)) {
            throw new RuntimeException("Failed creating uploads directory `{$uploadsPath}`.");
        }

        $filesystem = new Local([
            'name' => $name,
            'handle' => $handle,
            'path' => $uploadsPath,
        ]);

        if (!$fsService->saveFilesystem($filesystem)) {
            throw new RuntimeException('Unable to create upload test filesystem: ' . json_encode($filesystem->getErrors()));
        }

        $saved = $fsService->getFilesystemByHandle($handle);

        if (!$saved instanceof Local) {
            throw new RuntimeException("Upload test filesystem `{$handle}` was created but could not be reloaded.");
        }

        return $saved;
    }

    private static function setDefaultUploadVolume(Volume $volume): void
    {
        Formie::$plugin->getSettings()->defaultFileUploadVolume = 'folder:' . $volume->uid;
    }
}
