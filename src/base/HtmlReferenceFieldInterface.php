<?php
namespace verbb\formie\base;

use craft\base\ElementInterface;

/**
 * Fields whose reference value is formatted HTML rather than text.
 */
interface HtmlReferenceFieldInterface
{
    // Public Methods
    // =========================================================================

    /**
     * Returns purified HTML that is safe as element content, or `null` to output the value as text.
     * Text nodes may still contain quotes, so the result must never be placed in an attribute.
     */
    public function getValueForHtmlReference(mixed $value, ?ElementInterface $element = null): ?string;
}
