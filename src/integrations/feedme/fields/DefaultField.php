<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\DefaultField as FeedMeDefault;

class DefaultField extends FeedMeDefault
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function parseField(): mixed
    {
        $this->beforeParseField();
    }

}
