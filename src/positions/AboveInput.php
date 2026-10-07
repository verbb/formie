<?php
namespace verbb\formie\positions;

use verbb\formie\base\Position;

use Craft;

class AboveInput extends Position
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Above Input');
    }


    // Properties
    // =========================================================================

    protected static ?string $position = 'above';
}
