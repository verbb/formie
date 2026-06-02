<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\ChildFieldInterface;
use verbb\formie\fields\Dropdown;
use verbb\formie\helpers\SchemaHelper;

use Craft;

class DateAmPmDropdown extends DateDropdown implements ChildFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Date - AM/PM');
    }

    public static function getInputTemplatePath(): string
    {
        return 'fields/dropdown';
    }

    public static function getReferenceBlockTemplatePath(): string
    {
        return 'fields/dropdown';
    }
    

    // Properties
    // =========================================================================

    public string $validationFormatParam = 'a';
}
