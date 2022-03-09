<?php
namespace verbb\formie\models;

use craft\base\Element;

class FakeElement extends Element
{
    // Public Methods
    // =========================================================================

    public function getFieldValue(string $fieldHandle): mixed
    {
        return $this->getBehavior('customFields')->$fieldHandle;
    }
}
