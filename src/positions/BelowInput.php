<?php
namespace verbb\formie\positions;

use Craft;
use verbb\formie\base\Position;

class BelowInput extends Position
{
    // Protected Properties
    // =========================================================================

    protected static ?string $position = 'below';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Below Input');
    }
}
