<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\SubFieldInnerFieldInterface;
use verbb\formie\fields\Date;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\SchemaHelper;

use Craft;

class DateTime extends SingleLineText implements SubFieldInnerFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Date - Time');
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
            // Resolve limits from the parent when rendering, including cleared limits.
            $attributes['min'] = $parentField->getMinDate()?->format('H:i:s');
            $attributes['max'] = $parentField->getMaxDate()?->format('H:i:s');
        }

        return $attributes;
    }
}
