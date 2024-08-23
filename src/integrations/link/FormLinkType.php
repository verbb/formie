<?php
namespace verbb\formie\integrations\link;

use verbb\formie\elements\Form as FormElement;

use Craft;
use craft\fields\linktypes\BaseElementLinkType;

class FormLinkType extends BaseElementLinkType
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Formie Form');
    }

    protected static function elementType(): string
    {
        return FormElement::class;
    }


    // Protected Methods
    // =========================================================================

    protected function selectionCriteria(): array
    {
        return [];
    }
}
