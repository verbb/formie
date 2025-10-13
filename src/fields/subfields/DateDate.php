<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\SubFieldInnerFieldInterface;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\SchemaHelper;

use Craft;

class DateDate extends SingleLineText implements SubFieldInnerFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Date - Date');
    }

    public static function getFrontEndInputTemplatePath(): string
    {
        return 'fields/single-line-text';
    }

    public static function getEmailTemplatePath(): string
    {
        return 'fields/single-line-text';
    }
}
