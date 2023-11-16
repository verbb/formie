<?php
namespace verbb\formie\positions;

use Craft;
use verbb\formie\base\Position;

class Hidden extends Position
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Hidden');
    }
}
