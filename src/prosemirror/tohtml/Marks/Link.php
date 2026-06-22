<?php
namespace verbb\formie\prosemirror\tohtml\Marks;

use verbb\formie\helpers\StringHelper;

use Craft;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\validators\HandleValidator;

class Link extends Mark
{
    // Static Methods
    // =========================================================================

    private static function parseRefTags($value): array|string|null
    {
        $value = preg_replace_callback('/([^\'"\?#]*)(\?[^\'"\?#]+)?(#[^\'"\?#]+)?(?:#|%23)([\w]+)\:(\d+)(?:@(\d+))?(\:(?:transform\:)?' . HandleValidator::$handlePattern . ')?/', function($matches) {
            [, $url, $query, $hash, $elementType, $ref, $siteId, $transform] = array_pad($matches, 10, null);

            // Create the ref tag, and make sure :url is in there
            $ref = $elementType . ':' . $ref . ($siteId ? "@$siteId" : '') . ($transform ?: ':url');

            if ($query || $hash) {
                // Make sure that the query/hash isn't actually part of the parsed URL
                // - someone's Entry URL Format could include "?slug={slug}" or "#{slug}", etc.
                // - assets could include ?mtime=X&focal=none, etc.
                $parsed = Craft::$app->getElements()->parseRefs("{{$ref}}");

                if ($query) {
                    // Decode any HTML entities, e.g. &amp;
                    $query = Html::decode($query);

                    if (str_contains($parsed, $query)) {
                        $url .= $query;
                        $query = '';
                    }
                }
                if ($hash && str_contains($parsed, $hash)) {
                    $url .= $hash;
                    $hash = '';
                }
            }

            return '{' . $ref . '||' . $url . '}' . $query . $hash;
        }, $value);

        if (StringHelper::contains($value, '{')) {
            $value = Craft::$app->getElements()->parseRefs($value);
        }

        return $value;
    }

    private static function isInternalUrl(string $href): bool
    {
        $href = trim($href);

        if ($href === '' || str_starts_with($href, '#')) {
            return true;
        }

        // Craft element ref tags (e.g. `{entry:123:url}`).
        if (preg_match('/\{[\w]+:\d+/', $href)) {
            return true;
        }

        if (UrlHelper::isRootRelativeUrl($href)) {
            return true;
        }

        // Relative paths without a leading slash.
        if (!UrlHelper::isAbsoluteUrl($href) && !UrlHelper::isProtocolRelativeUrl($href)) {
            return true;
        }

        // Non-http(s) schemes (mailto:, tel:, etc.).
        if (UrlHelper::isAbsoluteUrl($href) && !preg_match('/^https?:\/\//i', $href)) {
            return true;
        }

        $linkHost = parse_url($href, PHP_URL_HOST);

        if ($linkHost === null && UrlHelper::isProtocolRelativeUrl($href)) {
            $linkHost = parse_url('https:' . $href, PHP_URL_HOST);
        }

        if ($linkHost === null) {
            return true;
        }

        if (!Craft::$app) {
            return false;
        }

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $siteHost = parse_url($site->getBaseUrl(), PHP_URL_HOST);

            if ($siteHost && strcasecmp($linkHost, $siteHost) === 0) {
                return true;
            }
        }

        return false;
    }


    // Properties
    // =========================================================================    

    protected ?string $markType = 'link';
    protected string|null|array $tagName = 'a';
    
    
    // Public Methods
    // =========================================================================

    public function tag(): array
    {
        $attrs = [];
        $href = $this->mark->attrs->href ?? '';

        if ($href) {
            $attrs['href'] = self::parseRefTags($href);
        }

        if (isset($this->mark->attrs->target)) {
            $attrs['target'] = $this->mark->attrs->target;

            if ($attrs['target'] === '_blank') {
                $resolvedHref = is_string($attrs['href'] ?? null) ? $attrs['href'] : $href;
                $isInternal = self::isInternalUrl($href) || self::isInternalUrl($resolvedHref);

                if (!$isInternal) {
                    $attrs['rel'] = 'noopener noreferrer nofollow';
                }
            }
        }

        return [
            [
                'tag' => $this->tagName,
                'attrs' => $attrs,
            ],
        ];
    }
}
