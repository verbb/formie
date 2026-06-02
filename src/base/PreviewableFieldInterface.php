<?php
namespace verbb\formie\base;

use craft\base\ElementInterface;

interface PreviewableFieldInterface
{
    // Public Methods
    // =========================================================================

    public function getPreviewHtml(mixed $value, ElementInterface $element): string;
}
