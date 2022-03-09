<?php
namespace verbb\formie\positions;

use Craft;
use verbb\formie\base\Position;

class RightInput extends Position
{
    // Protected Properties
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected static ?string $position = 'below';


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Right of Input');
    }
}
