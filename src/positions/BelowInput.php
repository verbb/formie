<?php
namespace verbb\formie\positions;

use Craft;
use verbb\formie\base\Position;

class BelowInput extends Position
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
        return Craft::t('formie', 'Below Input');
    }
}
