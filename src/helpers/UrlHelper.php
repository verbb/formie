<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;

use Craft;
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

    /**
     * Appends request query parameters as literal URL values.
     */
    public static function appendRequestQueryString(string $url): string
    {
        $request = Craft::$app->getRequest();

        if (!$request->getIsSiteRequest()) {
            return $url;
        }

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

        if ($params === []) {
            return $url;
        }

        $fragment = null;

        if (($hashPos = strrpos($url, '#')) !== false) {
            $fragment = substr($url, $hashPos + 1);
            $url = substr($url, 0, $hashPos);
        }

        $existingParams = [];
        $existingQuery = '';
        $baseUrl = $url;

        if (($queryPos = strpos($url, '?')) !== false) {
            $existingQuery = substr($url, $queryPos + 1);
            parse_str($existingQuery, $existingParams);
            $baseUrl = substr($url, 0, $queryPos);
        }

        // Explicit redirect parameters take precedence and remain byte-for-byte
        // intact so placeholders such as `{id}` can be resolved afterwards.
        $params = array_diff_key($params, $existingParams);
        $params = array_filter($params, static fn($value) => $value !== null && $value !== '');
        $requestQuery = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $query = implode('&', array_filter([$existingQuery, $requestQuery], static fn(string $value) => $value !== ''));

        if ($query === '') {
            return $baseUrl . ($fragment !== null ? '#' . $fragment : '');
        }

        $result = $baseUrl . '?' . $query;

        if ($fragment !== null) {
            $result .= '#' . $fragment;
        }

        return $result;
    }

}
