<?php
namespace verbb\formie\deprecations;

use Craft;
use verbb\formie\helpers\ArrayHelper;

final class ThemeConfigLegacyKeys
{
    // Static Methods
    // =========================================================================

    public static function canonicalDottedPathIfLegacy(string $dottedKey): ?string
    {
        $aliases = self::legacyMap();
        $first = str_contains($dottedKey, '.') ? strstr($dottedKey, '.', true) : $dottedKey;

        if (!isset($aliases[$first])) {
            return null;
        }

        if (!str_contains($dottedKey, '.')) {
            return $aliases[$first];
        }

        return $aliases[$first] . '.' . substr($dottedKey, strlen($first) + 1);
    }

    public static function canonicalSegmentIfLegacy(string $segment): ?string
    {
        return self::legacyMap()[$segment] ?? null;
    }

    public static function maybeLogDeprecatedDottedPath(string $caller, string $key): ?string
    {
        $canonicalKey = self::canonicalDottedPathIfLegacy($key);

        if ($canonicalKey === null) {
            return null;
        }

        Craft::$app->getDeprecator()->log($caller, "`themeConfig` path `{$key}` uses a deprecated root segment; use `{$canonicalKey}` instead (see `docs/theming/theme-config.md`).");

        return $canonicalKey;
    }

    public static function resolveSegmentWithDeprecation(string $caller, string $segment): string
    {
        $canonical = self::canonicalSegmentIfLegacy($segment);

        if ($canonical === null) {
            return $segment;
        }

        Craft::$app->getDeprecator()->log($caller, "`{$segment}` is a deprecated theme config key. Use `{$canonical}` instead (see `docs/theming/theme-config.md`).");

        return $canonical;
    }

    public static function getMergedThemeConfigItem(array $themeConfig, string $caller, string $key): mixed
    {
        if ($canonicalKey = self::maybeLogDeprecatedDottedPath($caller, $key)) {
            $fromCanonical = ArrayHelper::getValue($themeConfig, $canonicalKey, '__none__');

            if ($fromCanonical !== '__none__') {
                return $fromCanonical;
            }
        }

        return ArrayHelper::getValue($themeConfig, $key, []);
    }


    // Private Methods
    // =========================================================================

    private static function legacyMap(): array
    {
        return [
            'radio' => 'radioButtons',
            'date' => 'dateTime',
            'email' => 'emailAddress',
            'hidden' => 'hiddenField',
            'phone' => 'phoneNumber',
        ];
    }
}
