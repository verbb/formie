<?php
namespace verbb\formie\helpers;

use verbb\formie\elements\Submission;
use verbb\formie\references\ReferenceException;

use Craft;

final class CompletionRedirectPolicy
{
    // Static Methods
    // =========================================================================

    public static function resolve(string $template, Submission $submission): string
    {
        // This prefix comes from Craft's site configuration, never from a submitted field.
        if (str_starts_with($template, '{site:url}')) {
            $baseUrl = Craft::$app->getSites()->getSiteById($submission->siteId)?->getBaseUrl();

            if (!$baseUrl) {
                return '';
            }

            $template = $baseUrl . substr($template, strlen('{site:url}'));
        }

        try {
            return self::validate(References::resolveUrl($template, $submission));
        } catch (ReferenceException $e) {
            return '';
        }
    }

    public static function validateForSite(string $url, int $siteId): string
    {
        $url = self::validate($url);
        $origin = self::origin($url);

        if ($url === '' || $origin === '') {
            return $url;
        }

        $baseUrl = Craft::$app->getSites()->getSiteById($siteId)?->getBaseUrl();

        return $baseUrl && $origin === self::origin($baseUrl) ? $url : '';
    }

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

        return $url;
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
