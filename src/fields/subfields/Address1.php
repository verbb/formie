<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\ChildFieldInterface;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\subfields\traits\AddressTextSubmissionTrait;
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;

use Craft;

class Address1 extends SingleLineText implements ChildFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Address 1');
    }


    // Constants
    // =========================================================================

    protected const DEFAULT_INPUT_TEMPLATE_PATH = 'fields/single-line-text';
    protected const DEFAULT_REFERENCE_BLOCK_TEMPLATE_PATH = 'fields/single-line-text';
    protected const SUBMISSION_INPUT_ATTRIBUTES = [
        'data-formie-address-line1-input' => true,
        'autocomplete' => 'address-line1',
    ];


    // Traits
    // =========================================================================

    use AddressTextSubmissionTrait;

    // Protected Methods
    // =========================================================================

    protected function defineSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $tag = parent::defineSlotTag($key, $context);

        if ($tag && $key === 'fieldInput') {
            $tag->mergeCoreAttributes([
                'autocomplete' => 'address-line1',
                'data-formie-address-line1-input' => true,
            ]);
        }

        return $tag;
    }
}
