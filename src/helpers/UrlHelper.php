<?php
namespace verbb\formie\helpers;

use Craft;
use craft\errors\SiteNotFoundException;
use craft\helpers\UrlHelper as CraftUrlHelper;

class UrlHelper extends CraftUrlHelper
{
    // Static Methods
    // =========================================================================

    public static function siteActionUrl(string $path = '', array|string|null $params = null, ?string $scheme = null, ?bool $showScriptName = null): string
    {
        // Swap the domain to resolve to the current site for front-end requests.
        // Note that this should only be done for other domains, hence the check for host changes.
        // Otherwise, sub-directory installs would be affected.
        // https://github.com/verbb/formie/issues/2479
        $url = static::actionUrl($path, $params, $scheme, $showScriptName);
        $baseSiteUrl = parse_url(static::baseSiteUrl())['host'] ?? '';
        $baseCpUrl = parse_url(static::baseCpUrl())['host'] ?? '';

        return str_replace($baseCpUrl, $baseSiteUrl, $url);
    }

    public static function isSameSiteUrl(?string $url): bool
    {
        if (!$url) {
            return false;
        }

        if (static::isRootRelativeUrl($url)) {
            return true;
        }

        if (static::isProtocolRelativeUrl($url)) {
            $request = Craft::$app->getRequest();

            if ($request->getIsConsoleRequest()) {
                return false;
            }

            $url = $request->getScheme() . ':' . $url;
        }

        if (!static::isAbsoluteUrl($url)) {
            return false;
        }

        $baseCpUrl = static::baseCpUrl();

        if ($baseCpUrl && str_starts_with($url, $baseCpUrl)) {
            return true;
        }

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            try {
                $baseUrl = static::siteUrl('', null, null, $site->id);
            } catch (SiteNotFoundException) {
                continue;
            }

            if ($baseUrl && str_starts_with($url, $baseUrl)) {
                return true;
            }
        }

        return false;
    }

    public static function getSafeReferrerUrl(?string $fallback = null): string
    {
        $referrer = Craft::$app->getRequest()->getReferrer();

        if ($referrer && static::isSameSiteUrl($referrer)) {
            return $referrer;
        }

        return $fallback ?? static::siteUrl();
    }

}