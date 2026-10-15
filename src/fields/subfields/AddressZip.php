<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\ChildFieldInterface;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\subfields\traits\AddressTextSubmissionTrait;
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;

class AddressZip extends SingleLineText implements ChildFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Address - ZIP / Postal Code');
    }


    // Constants
    // =========================================================================

    protected const DEFAULT_INPUT_TEMPLATE_PATH = 'fields/single-line-text';
    protected const DEFAULT_REFERENCE_BLOCK_TEMPLATE_PATH = 'fields/single-line-text';
    protected const SUBMISSION_INPUT_ATTRIBUTES = [
        'data-formie-address-zip-input' => true,
        'autocomplete' => 'postal-code',
    ];


    // Traits
    // =========================================================================

    use AddressTextSubmissionTrait;


    // Public Methods
    // =========================================================================

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();
        $rules[] = [$this->handle, 'validateZip'];

        return $rules;
    }

    public function validateZip(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->valueKey());

        if (strlen($value) > 10) {
            $element->addError($this->valueKey(), Craft::t('formie', '"{label}" should contain at most {max, number} {max, plural, one{character} other{characters}}.', [
                'label' => $this->label,
                'max' => 10,
            ]));
        }
    }


    // Protected Methods
    // =========================================================================

    protected function defineSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $tag = parent::defineSlotTag($key, $context);

        if ($tag && $key === 'fieldInput') {
            $tag->mergeCoreAttributes([
                'autocomplete' => 'postal-code',
                'data-formie-address-zip-input' => true,
            ]);
        }

        return $tag;
    }
}
