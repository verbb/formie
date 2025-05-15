<?php
namespace verbb\formie\positions;

use Craft;
use verbb\formie\base\Position;

class LeftInput extends Position
{
    // Protected Properties
    // =========================================================================

    protected static ?string $position = 'above';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Left of Input');
    }
}
