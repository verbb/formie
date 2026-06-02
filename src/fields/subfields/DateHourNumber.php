<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\ChildFieldInterface;
use verbb\formie\fields\Number;
use verbb\formie\helpers\SchemaHelper;

use Craft;

class DateHourNumber extends DateNumber implements ChildFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Date - Hour');
    }

    public static function getInputTemplatePath(): string
    {
        return 'fields/number';
    }

    public static function getReferenceBlockTemplatePath(): string
    {
        return 'fields/number';
    }
    

    // Properties
    // =========================================================================

    public string $validationFormatParam = 'G';
}
