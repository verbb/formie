<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Element;

class FakeElement extends Element
{
    // Public Methods
    // =========================================================================

    public function getFieldValue(string $fieldHandle)
    {
        return $this->getBehavior('customFields')->$fieldHandle;
    }
}
