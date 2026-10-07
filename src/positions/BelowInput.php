<?php
namespace verbb\formie\positions;

use verbb\formie\base\Position;

use Craft;

class BelowInput extends Position
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Below Input');
    }


    // Properties
    // =========================================================================

    protected static ?string $position = 'below';
}
