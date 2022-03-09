<?php
namespace verbb\formie\positions;

use Craft;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\base\Position;
use verbb\formie\base\SubfieldInterface;

class FieldsetEnd extends Position
{
    // Protected Properties
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected static ?string $position = 'fieldset-end';


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
            $field instanceof SubfieldInterface;
    }

    /**
     * @inheritDoc
     */
    public static function fallback(FormFieldInterface $field = null): ?string
    {
        return BelowInput::class;
    }
}
