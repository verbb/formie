<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\ChildFieldInterface;
use verbb\formie\fields\SingleLineText;
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;

use Craft;

class Address3 extends SingleLineText implements ChildFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Address 3');
    }


    // Constants
    // =========================================================================

    protected const DEFAULT_INPUT_TEMPLATE_PATH = 'fields/single-line-text';
    protected const DEFAULT_REFERENCE_BLOCK_TEMPLATE_PATH = 'fields/single-line-text';

    // Protected Methods
    // =========================================================================

    protected function defineSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $tag = parent::defineSlotTag($key, $context);

        if ($tag && $key === 'fieldInput') {
            $tag->mergeCoreAttributes([
                'autocomplete' => 'address-line3',
                'data-formie-address-line3-input' => true,
            ]);
        }

        return $tag;
    }
}
