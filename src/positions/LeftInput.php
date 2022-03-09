<?php
namespace verbb\formie\positions;

use Craft;
use verbb\formie\base\Position;

class LeftInput extends Position
{
    // Protected Properties
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected static ?string $position = 'above';


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Left of Input');
    }
}
