<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;

use Craft;

final class CompletionRedirectPolicy
{
    // Static Methods
    // =========================================================================

    public static function validate(string $url): string
    {
        // Reject encoded controls and backslashes too: browsers, proxies and PHP
        // do not all parse these strings identically. Never repair an unsafe target.
        $decoded = $url;

        for ($i = 0; $i < 3; $i++) {
            $decoded = rawurldecode($decoded);
        }

        if ($url === '' || trim($url) !== $url || preg_match('/[\x00-\x1f\x7f\\\\]/', $decoded) || preg_match('/\s/', $url)
            || str_starts_with($decoded, '//') || str_contains($url, '{{') || str_contains($url, '{%')) {
            return '';
        }
        $parts = parse_url($url);

        if ($parts === false || isset($parts['user']) || isset($parts['pass'])) {
            return '';
        }

        if (!isset($parts['scheme'])) {
            // A relative path cannot supply a scheme/authority after decoding.
            return !isset($parts['host']) && !preg_match('/^[^\/?#]*:/', $decoded) ? $url : '';
        }

        if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true) || empty($parts['host'])) {
            return '';
        }
        $origin = self::origin($url);
        $allowed = Formie::$plugin->getSettings()->completionRedirectAllowedOrigins;

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $allowed[] = self::origin(Craft::getAlias($site->getBaseUrl()));
        }
        return in_array($origin, array_map(self::origin(...), $allowed), true) ? $url : '';
    }

    public static function origin(string $url): string
    {
        $parts = parse_url($url);

        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }
        $scheme = strtolower($parts['scheme']);
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        return $scheme . '://' . strtolower($parts['host']) . ':' . $port;
    }
}
