<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\ChildFieldInterface;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\Html;
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;

use Craft;

class Address2 extends SingleLineText implements ChildFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Address 2');
    }

    public static function getInputTemplatePath(): string
    {
        return 'fields/single-line-text';
    }

    public static function getReferenceBlockTemplatePath(): string
    {
        return 'fields/single-line-text';
    }


    // Protected Methods
    // =========================================================================

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $tag = parent::defineFieldSlotTag($key, $context);

        if ($tag && $key === 'fieldInput') {
            $tag->mergeCoreAttributes([
                'autocomplete' => 'address-line2',
                'data-address2' => true,
            ]);
        }

        return $tag;
    }
}
