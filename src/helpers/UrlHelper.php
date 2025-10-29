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

}