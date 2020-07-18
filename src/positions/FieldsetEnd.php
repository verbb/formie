<?php
namespace verbb\formie\positions;

use Craft;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\base\Position;
use verbb\formie\base\SubfieldInterface;
use verbb\formie\fields\formfields\Categories;
use verbb\formie\fields\formfields\Checkboxes;
use verbb\formie\fields\formfields\Radio;

class FieldsetEnd extends Position
{
    // Protected Properties
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected static $position = 'fieldset-end';


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Bottom of Fieldset');
    }

    /**
     * @inheritDoc
     */
    public static function supports(FormFieldInterface $field = null): bool
    {
        return $field instanceof NestedFieldInterface ||
            $field instanceof SubfieldInterface ||
            $field instanceof Checkboxes ||
            $field instanceof Radio ||
            $field instanceof Categories;
    }

    /**
     * @inheritDoc
     */
    public static function fallback(FormFieldInterface $field = null)
    {
        return BelowInput::class;
    }
}
