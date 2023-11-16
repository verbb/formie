<?php
namespace verbb\formie\base;

use craft\base\ComponentInterface;
use craft\base\ElementInterface;

interface SubfieldInterface extends ComponentInterface
{
    // Public Methods
    // =========================================================================

    public function getFrontEndSubfields($context): array;
    public function getSubfieldOptions(): array;
    public function validateRequiredFields(ElementInterface $element);
}
