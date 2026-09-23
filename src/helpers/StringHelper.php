<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;

use Craft;
use craft\helpers\StringHelper as CraftStringHelper;

use LitEmoji\LitEmoji;

use voku\helper\AntiXSS;

class StringHelper extends CraftStringHelper
{
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

    public static function emojiToShortcodes(string $str): string
    {
        // Add delimiters around all 4-byte chars
        $dl = '__MB4_DL__';
        $dr = '__MB4_DR__';
        $str = self::replaceMb4($str, fn($char) => sprintf('%s%s%s', $dl, $char, $dr));

        // Strip out consecutive delimiters
        $str = str_replace(sprintf('%s%s', $dr, $dl), '', $str);

        // Replace all 4-byte sequences individually
        return preg_replace_callback("/$dl(.+?)$dr/", fn($m) => LitEmoji::unicodeToShortcode($m[1]), $str);
    }

    public static function shortcodesToEmoji(string $str): string
    {
        return LitEmoji::shortcodeToUnicode($str);
    }

    public static function entitiesToEmoji(string $str): string
    {
        return LitEmoji::entitiesToUnicode($str);
    }

    public static function encodeHtml(string $str): string
    {
        // We can't use `LitEmoji::encodeHtml()` as we need to replace `LitEmoji::unicodeToShortcode()` 
        // with `self::emojiToShortcodes()` to handle some legitimate characters like `’` (U+2019).
        $str = self::emojiToShortcodes($str);

        return LitEmoji::shortcodeToEntities($str);
    }

    public static function cleanString(string $str): string
    {
        $antiXss = new AntiXSS();
        
        // Allow inline CSS for rich text
        $antiXss->removeEvilAttributes(['style']);

        return $antiXss->xss_clean((string)$str);
    }

    public static function sanitizeRedirectUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        $cleanUrl = preg_replace('/[\x00-\x1F\x7F]+/u', '', $url) ?? $url;
        $decodedUrl = html_entity_decode($cleanUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalizedUrl = preg_replace('/[\x00-\x20\x7F]+/u', '', $decodedUrl) ?? $decodedUrl;
        $normalizedUrl = str_replace('\\', '/', $normalizedUrl);

        if (str_starts_with($normalizedUrl, '//')) {
            return '';
        }

        if (preg_match('/^([a-z][a-z0-9+\-.]*):/i', $normalizedUrl, $matches) && !in_array(strtolower($matches[1]), ['http', 'https'], true)) {
            return '';
        }

        return parse_url($decodedUrl) === false ? '' : $cleanUrl;
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
}
