<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\ChildFieldInterface;

use Craft;

class DateDayDropdown extends DateDropdown implements ChildFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Date - Day');
    }


    // Constants
    // =========================================================================

    protected const DEFAULT_INPUT_TEMPLATE_PATH = 'fields/dropdown';
    protected const DEFAULT_REFERENCE_BLOCK_TEMPLATE_PATH = 'fields/dropdown';


    // Properties
    // =========================================================================

    public string $validationFormatParam = 'j';
}
