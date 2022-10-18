<?php
namespace verbb\formie\helpers;

use Craft;
use craft\helpers\App;
use craft\helpers\FileHelper as CraftFileHelper;
use craft\helpers\StringHelper;

use Throwable;

class FileHelper
{
    // Static Methods
    // =========================================================================

    /**
     * Copies template files from one directory to another.
     *
     * @param string $from
     * @param string $to
     * @return bool
     */
    public static function copyTemplateDirectory(string $from, string $to): bool
    {
        $from = App::parseEnv($from);
        $to = App::parseEnv($to);

        try {
            $templates = Craft::$app->getPath()->getSiteTemplatesPath();

            if (!StringHelper::contains($to, $templates)) {
                $to = CraftFileHelper::normalizePath($templates . DIRECTORY_SEPARATOR . $to);
            }

            if (is_dir($to) && !CraftFileHelper::isDirectoryEmpty($to)) {
                return false;
            }

            CraftFileHelper::copyDirectory($from, $to);
        } catch (Throwable $e) {
            return false;
        }

        return true;
    }

    public static function doesSitePathExist(string $path): bool
    {
        $templatesPath = Craft::$app->getPath()->getSiteTemplatesPath();

        $basePaths = [$templatesPath];

        // Should we be looking for a localized version of the template?
        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $sitePath = $templatesPath . DIRECTORY_SEPARATOR . $site->handle;
            
            if (is_dir($sitePath)) {
                $basePaths[] = $sitePath;
            }
        }

        foreach ($basePaths as $basePath) {
            $fullPath = CraftFileHelper::normalizePath($basePath . DIRECTORY_SEPARATOR . $path);

            if (is_dir($fullPath) || file_exists($fullPath)) {
                return true;
            }
        }

        return false;
    }
}
