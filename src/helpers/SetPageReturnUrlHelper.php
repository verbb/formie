<?php
namespace verbb\formie\helpers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Request;

final class SetPageReturnUrlHelper
{
    public const QUERY_PARAM = 'setPageReturnToken';

    /**
     * Build a signed token for the current site request path + query (root-relative).
     * Returns null when the request is not a normal site request (e.g. console).
     */
    public static function createTokenFromCurrentRequest(?Request $request = null): ?string
    {
        $request = $request ?? Craft::$app->getRequest();

        if (!$request->getIsSiteRequest()) {
            return null;
        }

        $relative = self::relativePathAndQueryFromRequest($request);

        if ($relative === null) {
            return null;
        }

        return Craft::$app->getSecurity()->hashData($relative);
    }

    /**
     * Resolve where legacy multipage tab navigation should redirect after set-page.
     * Prefers a validated signed token; never trusts Referer.
     */
    public static function resolveLegacySetPageRedirectUrl(Request $request): string
    {
        $token = $request->getParam(self::QUERY_PARAM);

        if (is_string($token) && trim($token) !== '') {
            $relative = Craft::$app->getSecurity()->validateData($token);

            if (is_string($relative) && self::isSafeRootRelativePathAndQuery($relative)) {
                return self::toAbsoluteSiteUrl($relative);
            }
        }

        $pathInfo = trim((string)$request->getPathInfo(), '/');

        return UrlHelper::siteUrl($pathInfo);
    }

    public static function relativePathAndQueryFromRequest(Request $request): ?string
    {
        $pathSegment = trim((string)$request->getPathInfo(), '/');

        if ($pathSegment === '') {
            $urlPath = parse_url((string)$request->getUrl(), PHP_URL_PATH);
            if (is_string($urlPath) && $urlPath !== '') {
                $pathSegment = trim($urlPath, '/');
                if (str_starts_with($pathSegment, 'index.php/')) {
                    $pathSegment = trim(substr($pathSegment, strlen('index.php/')), '/');
                } elseif ($pathSegment === 'index.php') {
                    $pathSegment = '';
                }
            }
        }

        $path = $pathSegment === '' ? '/' : '/' . $pathSegment;

        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        $params = $request->getQueryParams();
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        return $path . ($query !== '' ? '?' . $query : '');
    }

    public static function isSafeRootRelativePathAndQuery(string $relative): bool
    {
        if (str_contains($relative, "\0") || str_contains($relative, "\n") || str_contains($relative, "\r")) {
            return false;
        }

        if (str_contains($relative, '#')) {
            return false;
        }

        $parsed = parse_url($relative);

        if ($parsed === false) {
            return false;
        }

        if (($parsed['scheme'] ?? null) !== null || ($parsed['host'] ?? null) !== null) {
            return false;
        }

        $path = $parsed['path'] ?? '';

        if ($path === '' || ($path[0] ?? '') !== '/') {
            return false;
        }

        if (str_starts_with($path, '//')) {
            return false;
        }

        if (str_contains($path, '\\')) {
            return false;
        }

        return true;
    }

    public static function toAbsoluteSiteUrl(string $rootRelativePathAndQuery): string
    {
        $parsed = parse_url($rootRelativePathAndQuery);

        if ($parsed === false) {
            return UrlHelper::siteUrl();
        }

        $path = $parsed['path'] ?? '/';

        if ($path === '' || ($path[0] ?? '') !== '/') {
            $path = '/' . ltrim($path, '/');
        }

        $trimmed = trim($path, '/');
        $url = $trimmed === '' ? UrlHelper::siteUrl() : UrlHelper::siteUrl($trimmed);

        if (!empty($parsed['query'])) {
            $url .= (str_contains($url, '?') ? '&' : '?') . $parsed['query'];
        }

        return $url;
    }
}
