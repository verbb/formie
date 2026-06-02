<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;

use Craft;
use craft\helpers\HtmlPurifier;
use craft\helpers\StringHelper as CraftStringHelper;

use HTMLPurifier_Config;

class StringHelper extends CraftStringHelper
{
    private const WORD_PATTERN = '/[\p{L}\p{N}\p{M}]+(?:[\'’._-][\p{L}\p{N}\p{M}]+)*/u';
    private const ALLOWED_URL_PROTOCOLS = ['http', 'https', 'mailto', 'tel', 'ftp'];

    // Static Methods
    // =========================================================================

    public static function toId(mixed $value, bool $allowNull = true): ?int
    {
        if ($allowNull && ($value === null || $value === '')) {
            return null;
        }

        if ($value === null || is_scalar($value)) {
            return (int)$value;
        }

        return null;
    }

    public static function cleanString(string $string): string
    {
        return HtmlPurifier::process($string);
    }

    public static function sanitizeMessageHtml(string $string): string
    {
        return trim(HtmlPurifier::process($string));
    }

    public static function sanitizeMessageHtmlRecursive(mixed $value): mixed
    {
        if (is_string($value)) {
            return self::sanitizeMessageHtml($value);
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = self::sanitizeMessageHtmlRecursive($item);
            }
        }

        return $value;
    }

    public static function sanitizeUrlAttribute(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $decodedUrl = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($decodedUrl === '') {
            return null;
        }

        $cleanUrl = preg_replace('/[\x00-\x1F\x7F]+/u', '', $decodedUrl) ?? $decodedUrl;
        $normalizedUrl = preg_replace('/\s+/u', '', $cleanUrl) ?? $cleanUrl;

        if (preg_match('/^([a-z][a-z0-9+\-.]*):/i', $normalizedUrl, $matches)) {
            $scheme = strtolower($matches[1]);

            if (!in_array($scheme, self::ALLOWED_URL_PROTOCOLS, true)) {
                return null;
            }
        }

        return $cleanUrl;
    }

    public static function sanitizeRedirectUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '' || str_starts_with($url, '//')) {
            return '';
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return '';
        }

        $scheme = strtolower((string)($parts['scheme'] ?? ''));

        if ($scheme !== '' && !in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        return $url;
    }

    public static function normalizePlainText(string $string): string
    {
        $string = self::convertToUtf8($string);
        $normalized = preg_replace('/[^\P{C}\t\n\r]/u', '', $string);

        return $normalized ?? $string;
    }

    public static function sanitizePlainTextInput(string $string): string
    {
        $string = self::normalizePlainText($string);
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', '');

        return html_entity_decode(HtmlPurifier::process($string, $config), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function decdec(string $str): string
    {
        $key = Formie::$plugin->getSettings()->getSecurityKey();

        if (strncmp($str, 'base64:', 7) === 0) {
            $str = base64_decode(substr($str, 7));
        }

        if (strncmp($str, 'crypt:', 6) === 0) {
            $str = Craft::$app->getSecurity()->decryptByKey(substr($str, 6), $key);
        }

        return $str;
    }

    public static function encenc(string $str): string
    {
        $key = Formie::$plugin->getSettings()->getSecurityKey();

        return 'base64:' . base64_encode('crypt:' . Craft::$app->getSecurity()->encryptByKey($str, $key));
    }

    public static function getCharacterCount(string $value): int
    {
        $text = self::getPlainText($value);

        // Browser `maxlength` counts newline characters as one character, so
        // normalize transport-specific CRLF pairs before comparing lengths.
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Prefer grapheme clusters so emoji, ZWJ sequences, accents, and other
        // composed glyphs count the same way users visually perceive them.
        if (function_exists('grapheme_strlen')) {
            return grapheme_strlen($text);
        }

        return mb_strlen($text, 'UTF-8');
    }

    public static function getWordCount(string $value): int
    {
        $text = self::normalizeText($value);

        if ($text === '') {
            return 0;
        }

        preg_match_all(self::WORD_PATTERN, $text, $matches);

        return count($matches[0] ?? []);
    }

    public static function normalizeText(string $value): string
    {
        $text = self::getPlainText($value);

        // Normalize whitespace (replace tabs/newlines/multiple spaces with single space)
        return trim(preg_replace('/[\s\t\n\r]+/', ' ', $text));
    }

    private static function getPlainText(string $value): string
    {
        // Strip all HTML tags (if any)
        $text = strip_tags($value);

        // Decode HTML entities (e.g. &#x1F389; → 🎉)
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}