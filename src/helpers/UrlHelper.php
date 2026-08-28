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
        $baseSiteUrlHost = parse_url(static::baseSiteUrl())['host'] ?? '';
        $baseCpUrlHost = parse_url(static::baseCpUrl())['host'] ?? '';
        $urlHost = parse_url($url)['host'] ?? '';

        // Only swap when the URL host matches the CP host exactly. Otherwise, nested subdomains
        // like `my-project.staging.example.com` can be corrupted when replacing `staging.example.com`.
        if ($baseCpUrlHost && $baseSiteUrlHost && $urlHost === $baseCpUrlHost && $baseCpUrlHost !== $baseSiteUrlHost) {
            return str_replace($baseCpUrlHost, $baseSiteUrlHost, $url);
        }

        return $url;
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

    public static function getSubmissionReturnUrl(?int $siteId = null): string
    {
        $request = Craft::$app->getRequest();

        $returnUrl = $request->getValidatedBodyParam('returnUrl');

        if ($returnUrl && static::isSameSiteUrl($returnUrl)) {
            return $returnUrl;
        }

        $referrer = $request->getReferrer();

        if ($referrer && static::isSameSiteUrl($referrer)) {
            return $referrer;
        }

        if ($siteId) {
            return static::siteUrl($request->getPathInfo(), null, null, $siteId);
        }

        return static::siteUrl();
    }

    /**
     * Appends the current request's query string params to a URL as literal values.
     *
     * Unlike Craft's `UrlHelper::url()`, `{` and `}` are encoded so values cannot be
     * interpreted as object-template syntax when the URL is rendered later.
     */
    public static function appendRequestQueryString(string $url): string
    {
        $request = Craft::$app->getRequest();

        if (!$request->getIsSiteRequest()) {
            return $url;
        }

        $queryParams = static::getRedirectQueryParams();

        if ($queryParams === []) {
            return $url;
        }

        $fragment = null;

        if (($hashPos = strrpos($url, '#')) !== false) {
            $fragment = substr($url, $hashPos + 1);
            $url = substr($url, 0, $hashPos);
        }

        $existingParams = [];
        $baseUrl = $url;

        if (($queryPos = strpos($url, '?')) !== false) {
            parse_str(substr($url, $queryPos + 1), $existingParams);
            $baseUrl = substr($url, 0, $queryPos);
        }

        // Request params first; params already on the redirect URL take precedence.
        $mergedParams = array_merge($queryParams, $existingParams);
        $mergedParams = array_filter($mergedParams, static fn($value) => $value !== null && $value !== '');

        if ($mergedParams === []) {
            return $baseUrl . ($fragment !== null ? '#' . $fragment : '');
        }

        $query = http_build_query($mergedParams, '', '&', PHP_QUERY_RFC3986);
        $result = $baseUrl . '?' . $query;

        if ($fragment !== null) {
            $result .= '#' . $fragment;
        }

        return $result;
    }

    /**
     * Returns query string params from the current request that are safe to append to redirect URLs.
     *
     * @return array<string, mixed>
     */
    public static function getRedirectQueryParams(): array
    {
        $request = Craft::$app->getRequest();
        $params = $request->getQueryParams();
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        $exclude = array_filter([
            'action',
            $generalConfig->pathParam,
            $generalConfig->tokenParam,
            $request->csrfParam,
            'x-craft-preview',
            'x-craft-live-preview',
        ]);

        foreach ($exclude as $key) {
            unset($params[$key]);
        }

        return $params;
    }

}