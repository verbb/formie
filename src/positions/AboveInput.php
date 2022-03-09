<?php
namespace verbb\formie\positions;

use Craft;
use verbb\formie\base\Position;

class AboveInput extends Position
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
        return Craft::t('formie', 'Above Input');
    }
}
