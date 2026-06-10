<?php
namespace verbb\formie\helpers;

use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\RichText;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\models\Section;

class RichTextHelper
{
    // Static Methods
    // =========================================================================

    public static function getRichTextConfig(string $key): array
    {
        $config = self::_getDefaultConfig();
        $fileConfig = self::_getConfig('formie', 'rich-text.json');

        // Override defaults with any file-based config
        if (is_array($fileConfig)) {
            foreach ($fileConfig as $k => $v) {
                $config[$k] = array_merge($config[$k], $v);
            }
        }

        $config = ArrayHelper::getValue($config, $key);

        // Add some extra variables
        $config['linkOptions'] = self::_getLinkOptions();

        return $config;
    }

    public static function getHtmlContent(mixed $content, ?Submission $submission = null, bool $nl2br = true): string
    {
        return RichText::from($content)->toHtml($submission, $nl2br);
    }

    public static function normalizeNodes(mixed $content): array|string
    {
        return RichText::normalizeNodes($content);
    }


    // Private Methods
    // =========================================================================

    private static function _getDefaultConfig(): array
    {
        return [
            'forms' => [
                'submitActionMessage' => [
                    'buttons' => ['bold', 'italic', 'variableTag'],
                    'rows' => 3,
                ],
                'errorMessage' => [
                    'buttons' => ['bold', 'italic'],
                    'rows' => 3,
                ],
                'requireUserMessage' => [
                    'buttons' => ['bold', 'italic'],
                    'rows' => 3,
                ],
                'scheduleFormPendingMessage' => [
                    'buttons' => ['bold', 'italic'],
                    'rows' => 3,
                ],
                'scheduleFormExpiredMessage' => [
                    'buttons' => ['bold', 'italic'],
                    'rows' => 3,
                ],
                'limitSubmissionsMessage' => [
                    'buttons' => ['bold', 'italic'],
                    'rows' => 3,
                ],
                'limitSubmissionsIpAddressMessage' => [
                    'buttons' => ['bold', 'italic'],
                    'rows' => 3,
                ],
            ],
            'fields' => [
                'agree' => [
                    'buttons' => ['bold', 'italic', 'link'],
                    'rows' => 3,
                ],
                'content' => [
                    'buttons' => ['bold', 'italic', 'underline', 'link', 'ulist', 'olist', 'heading2', 'heading3', 'paragraph'],
                    'rows' => 8,
                ],
                'calculations' => [
                    'buttons' => ['variableTag'],
                    'rows' => 3,
                ],
            ],
            'notifications' => [
                'content' => [
                    'buttons' => ['bold', 'italic', 'variableTag'],
                ],
            ],
        ];
    }

    private static function _getConfig(string $dir, string $file = null): bool|array
    {
        if (!$file) {
            return false;
        }

        $path = Craft::$app->getPath()->getConfigPath() . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $file;

        if (!is_file($path)) {
            return false;
        }

        return Json::decode(file_get_contents($path));
    }

    private static function _getLinkOptions(): array
    {
        $linkOptions = [];

        $sectionSources = self::_getSectionSources();
        $categorySources = self::_getCategorySources();
        $volumeSources = self::_getVolumeKeys();

        if (!empty($sectionSources)) {
            $linkOptions[] = [
                'optionTitle' => Craft::t('formie', 'Link to an entry'),
                'elementType' => Entry::class,
                'refHandle' => Entry::refHandle(),
                'sources' => $sectionSources,
                'criteria' => ['uri' => ':notempty:'],
            ];
        }

        if (!empty($volumeSources)) {
            $linkOptions[] = [
                'optionTitle' => Craft::t('formie', 'Link to an asset'),
                'elementType' => Asset::class,
                'refHandle' => Asset::refHandle(),
                'sources' => $volumeSources,
            ];
        }

        if (!empty($categorySources)) {
            $linkOptions[] = [
                'optionTitle' => Craft::t('formie', 'Link to a category'),
                'elementType' => Category::class,
                'refHandle' => Category::refHandle(),
                'sources' => $categorySources,
            ];
        }

        // Fill in any missing ref handles
        foreach ($linkOptions as &$linkOption) {
            if (!isset($linkOption['refHandle'])) {
                /** @var ElementInterface|string $class */
                $class = $linkOption['elementType'];
                $linkOption['refHandle'] = $class::refHandle() ?? $class;
            }
        }

        return $linkOptions;
    }

    private static function _getSectionSources(): array
    {
        $sources = [];
        $sections = Craft::$app->getEntries()->getAllSections();
        $showSingles = false;

        $sites = Craft::$app->getSites()->getAllSites();

        foreach ($sections as $section) {
            if ($section->type === Section::TYPE_SINGLE) {
                $showSingles = true;
            } else {
                $sectionSiteSettings = $section->getSiteSettings();

                foreach ($sites as $site) {
                    if (isset($sectionSiteSettings[$site->id]) && $sectionSiteSettings[$site->id]->hasUrls) {
                        $sources[] = 'section:' . $section->uid;
                    }
                }
            }
        }

        if ($showSingles) {
            array_unshift($sources, 'singles');
        }

        if (!empty($sources)) {
            array_unshift($sources, '*');
        }

        return $sources;
    }

    private static function _getCategorySources(): array
    {
        $sources = [];
        $categoryGroups = Craft::$app->getCategories()->getAllGroups();

        $sites = Craft::$app->getSites()->getAllSites();

        foreach ($categoryGroups as $categoryGroup) {
            $categoryGroupSiteSettings = $categoryGroup->getSiteSettings();

            foreach ($sites as $site) {
                if (isset($categoryGroupSiteSettings[$site->id]) && $categoryGroupSiteSettings[$site->id]->hasUrls) {
                    $sources[] = 'group:' . $categoryGroup->uid;
                }
            }
        }

        return $sources;
    }

    private static function _getVolumeKeys(): array
    {
        $allVolumes = Craft::$app->getVolumes()->getAllVolumes();
        $allowedVolumes = [];
        $userService = Craft::$app->getUser();

        foreach ($allVolumes as $volume) {
            if (($userService->checkPermission("viewAssets:{$volume->uid}"))) {
                $allowedVolumes[] = 'volume:' . $volume->uid;
            }
        }

        return $allowedVolumes;
    }
}
