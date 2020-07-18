<?php
namespace verbb\formie\fields;

use verbb\formie\elements\Form;
use verbb\formie\elements\db\FormQuery;

use Craft;
use craft\fields\BaseRelationField;

class Forms extends BaseRelationField
{
    // Public Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Forms (Formie)');
    }

    public static function defaultSelectionLabel(): string
    {
        return Craft::t('formie', 'Add a form');
    }

    public static function valueType(): string
    {
        return FormQuery::class;
    }

    protected static function elementType(): string
    {
        return Form::class;
    }
}
