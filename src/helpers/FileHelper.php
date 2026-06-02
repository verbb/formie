<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\helpers\StringHelper;

use Craft;
use craft\helpers\App;
use craft\helpers\FileHelper as CraftFileHelper;
use craft\web\View;

use Throwable;

class FileHelper
{
    // Static Methods
    // =========================================================================

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

    public static function readFileContents(string $filePath, ?string $context = null): ?string
    {
        if ($filePath === '') {
            Craft::warning('File path is empty.', $context ?? __METHOD__);

            return null;
        }

        if (!is_file($filePath)) {
            Craft::warning("File does not exist at path \"{$filePath}\".", $context ?? __METHOD__);

            return null;
        }

        if (!is_readable($filePath)) {
            Craft::warning("File is not readable at path \"{$filePath}\".", $context ?? __METHOD__);

            return null;
        }

        $contents = @file_get_contents($filePath);

        if (!is_string($contents)) {
            Craft::warning("Unable to read file contents at path \"{$filePath}\".", $context ?? __METHOD__);

            return null;
        }

        return $contents;
    }

    public static function readPluginTemplateContents(string $templatePath, ?string $context = null, bool $logFailures = true): ?string
    {
        $normalizedPath = preg_replace('/^formie\//', '', $templatePath);

        if (!$normalizedPath) {
            if ($logFailures) {
                Craft::warning("Template path \"{$templatePath}\" is invalid.", $context ?? __METHOD__);
            }

            return null;
        }

        $basePath = Formie::$plugin->getBasePath() . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
        $relativePath = str_replace('/', DIRECTORY_SEPARATOR, $normalizedPath);
        $resolvedPath = CraftFileHelper::normalizePath($basePath . $relativePath);

        $extension = pathinfo($resolvedPath, PATHINFO_EXTENSION);
        $candidatePaths = [$resolvedPath];

        if ($extension === '') {
            $candidatePaths[] = "{$resolvedPath}.html";
            $candidatePaths[] = "{$resolvedPath}.twig";
        }

        foreach ($candidatePaths as $candidatePath) {
            if (!is_file($candidatePath) || !is_readable($candidatePath)) {
                continue;
            }

            $contents = self::readFileContents($candidatePath, $context ?? __METHOD__);

            if ($contents !== null) {
                return $contents;
            }
        }

        if ($logFailures) {
            Craft::warning("Could not load plugin template contents for \"{$templatePath}\".", $context ?? __METHOD__);
        }

        return null;
    }

    public static function readResolvedTemplateContents(string $templatePath, int|string $templateMode = View::TEMPLATE_MODE_CP, ?string $context = null, bool $logFailures = true): ?string
    {
        $resolvedTemplate = Craft::$app->getView()->resolveTemplate($templatePath, $templateMode);

        if (!$resolvedTemplate) {
            if ($logFailures) {
                Craft::warning("Could not resolve template \"{$templatePath}\" in mode \"{$templateMode}\".", $context ?? __METHOD__);
            }

            return null;
        }

        return self::readFileContents($resolvedTemplate, $context ?? __METHOD__);
    }

    public static function readTemplateContents(string $templatePath, int|string $templateMode = View::TEMPLATE_MODE_CP, ?string $context = null): ?string
    {
        // Fast-path Formie-owned templates directly from disk.
        if (str_starts_with($templatePath, 'formie/')) {
            $contents = self::readPluginTemplateContents($templatePath, $context, false);

            if ($contents !== null) {
                return $contents;
            }
        }

        // Fallback to Craft template resolution to support custom module/plugin templates.
        $contents = self::readResolvedTemplateContents($templatePath, $templateMode, $context, false);

        if ($contents !== null) {
            return $contents;
        }

        Craft::warning("Could not load template contents for \"{$templatePath}\".", $context ?? __METHOD__);

        return null;
    }
}
