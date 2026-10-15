<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\ChildFieldInterface;

use Craft;

class DateHourNumber extends DateNumber implements ChildFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Date - Hour');
    }


    // Constants
    // =========================================================================

    protected const DEFAULT_INPUT_TEMPLATE_PATH = 'fields/number';
    protected const DEFAULT_REFERENCE_BLOCK_TEMPLATE_PATH = 'fields/number';


    // Properties
    // =========================================================================

    public string $validationFormatParam = 'G';
}
