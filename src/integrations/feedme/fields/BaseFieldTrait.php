<?php
namespace verbb\formie\integrations\feedme\fields;

trait BaseFieldTrait
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function beforeParseField(): void
    {
        // Feed Me assumes all fields are available in the global scope, so we fix that here.
        // We could also submit a PR to fix this at some point...
        $this->field = $this->element->getFieldLayout()->getFieldByHandle($this->fieldHandle);
    }

    /**
     * @inheritDoc
     */
    public function parseField(): mixed
    {
        $this->beforeParseField();
    }
}