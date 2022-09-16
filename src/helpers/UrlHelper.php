<?php
namespace verbb\formie\helpers;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper as CraftUrlHelper;

class UrlHelper
{
    // Static Methods
    // =========================================================================

    public static function siteActionUrl(string $path = '', $params = null, string $protocol = null): string
    {
        // Force `addTrailingSlashesToUrls` to `false` while we generate the redirectUri
        $addTrailingSlashesToUrls = Craft::$app->getConfig()->getGeneral()->addTrailingSlashesToUrls;
        Craft::$app->getConfig()->getGeneral()->addTrailingSlashesToUrls = false;

        $redirectUri = CraftUrlHelper::actionUrl($path, $params, $protocol);

        // Set `addTrailingSlashesToUrls` back to its original value
        Craft::$app->getConfig()->getGeneral()->addTrailingSlashesToUrls = $addTrailingSlashesToUrls;

        // We don't want the CP trigger showing in the action URL.
        $redirectUri = str_replace(Craft::$app->getConfig()->getGeneral()->cpTrigger . '/', '', $redirectUri);

        if (Craft::$app->getConfig()->getGeneral()->usePathInfo) {
            $redirectUri = str_replace('/index.php', '', $redirectUri);
        }

        // Stip the site query string - involved so as not to mess up a potential action query param
        $params = self::_extractParams($redirectUri);
        $url = $params[0] ?? '';
        $queryParams = $params[1] ?? [];
        $fragment = $params[2] ?? null;

        foreach ($queryParams as $key => $queryParam) {
            if ($key === 'site') {
                unset($queryParams[$key]);
            }
        }

        return self::_buildUrl($url, $queryParams, $fragment);
    }

    /**
     * Rebuilds a URL with params and a fragment.
     *
     * @param string $url
     * @param array $params
     * @param string|null $fragment
     * @return string
     */
    private static function _buildUrl(string $url, array $params, ?string $fragment): string
    {
        if (($query = CraftUrlHelper::buildQuery($params)) !== '') {
            $url .= '?' . $query;
        }

        if ($fragment !== null) {
            $url .= '#' . $fragment;
        }

        return $url;
    }

    /**
     * Normalizes query string params.
     *
     * @param string|array|null $params
     * @return array
     */
    private static function _normalizeParams(array|string|null $params): array
    {
        // If it's already an array, just split out the fragment and return
        if (is_array($params)) {
            $fragment = ArrayHelper::remove($params, '#');
            return [$params, $fragment];
        }

        $fragment = null;

        if (is_string($params)) {
            $params = ltrim($params, '?&');

            if (($fragmentPos = strpos($params, '#')) !== false) {
                $fragment = substr($params, $fragmentPos + 1);
                $params = substr($params, 0, $fragmentPos);
            }

            parse_str($params, $arr);
        } else {
            $arr = [];
        }

        return [$arr, $fragment];
    }

    /**
     * Extracts the params and fragment from a given URL, and merges those with another set of params.
     *
     * @param string $url
     * @return array
     */
    private static function _extractParams(string $url): array
    {
        if (($queryPos = strpos($url, '?')) === false && ($queryPos = strpos($url, '#')) === false) {
            return [$url, [], null];
        }

        [$params, $fragment] = self::_normalizeParams(substr($url, $queryPos));
        return [substr($url, 0, $queryPos), $params, $fragment];
    }
}
