<?php
namespace verbb\formie\base;

use craft\base\ComponentInterface;
use craft\base\ElementInterface;

interface SubFieldInterface extends ComponentInterface
{
    // Public Methods
    // =========================================================================

    public function getFrontEndSubFields($context): array;
    public function getSubFieldOptions(): array;
    public function validateRequiredFields(ElementInterface $element);
}
