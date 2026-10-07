<?php
namespace verbb\formie\positions;

use verbb\formie\base\Position;

use Craft;

class RightInput extends Position
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Right of Input');
    }


    // Properties
    // =========================================================================

    protected static ?string $position = 'below';
}
