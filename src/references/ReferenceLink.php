<?php
namespace verbb\formie\references;

/** A linked label, such as an entry's title and URL. HTML content gets a link, and anything else gets the label. */
final readonly class ReferenceLink
{
    // Static Methods
    // =========================================================================

    public static function contains(mixed $value): bool
    {
        if ($value instanceof self) {
            return true;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (self::contains($item)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function toText(mixed $value): mixed
    {
        if ($value instanceof self) {
            return $value->label;
        }

        return is_array($value) ? array_map(self::toText(...), $value) : $value;
    }

    public static function toHtml(mixed $value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map(self::toHtml(...), $value));
        }

        if (!$value instanceof self) {
            return htmlspecialchars(is_scalar($value) ? (string)$value : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $label = htmlspecialchars($value->label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // An element without a URL, such as an entry in a section without URIs, keeps its label.
        if ($value->url === null || $value->url === '') {
            return $label;
        }

        return '<a href="' . htmlspecialchars($value->url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . $label . '</a>';
    }


    // Public Methods
    // =========================================================================

    public function __construct(
        public string $label,
        public ?string $url,
    ) {
    }
}
