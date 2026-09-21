<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\SubFieldInnerFieldInterface;
use verbb\formie\fields\Date;
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


    // Public Methods
    // =========================================================================

    public function getInputAttributes(): array
    {
        $attributes = parent::getInputAttributes();
        $parentField = $this->getParentField();

        if ($parentField instanceof Date && $parentField->displayType === 'calendar') {
            // Saved subfield attributes can be missing or stale, especially for relative dates.
            // Include null values so removing a limit also clears its saved attribute.
            $attributes['min'] = $parentField->getMinDate()?->format('Y-m-d');
            $attributes['max'] = $parentField->getMaxDate()?->format('Y-m-d');
        }

        return $attributes;
    }
}
