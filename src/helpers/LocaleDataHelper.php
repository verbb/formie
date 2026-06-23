<?php
namespace verbb\formie\helpers;

use Craft;
use Locale;

class LocaleDataHelper
{
    // Static Methods
    // =========================================================================

    public static function languageName(string $code, ?string $locale = null): string
    {
        $locale ??= Craft::$app->getLocale()->getLanguageID();
        $code = strtolower(trim($code));

        if ($code === '') {
            return '';
        }

        $name = Locale::getDisplayLanguage($code, $locale);

        if ($name === false || $name === '') {
            return $code;
        }

        return $name;
    }

    public static function localeDisplayName(string $tag, ?string $locale = null): string
    {
        $locale ??= Craft::$app->getLocale()->getLanguageID();
        $icuTag = str_replace('-', '_', trim($tag));

        if ($icuTag === '') {
            return '';
        }

        $name = Locale::getDisplayName($icuTag, $locale);

        if ($name === false || $name === '') {
            return $tag;
        }

        return $name;
    }
}
