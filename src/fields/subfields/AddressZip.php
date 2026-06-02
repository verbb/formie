<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\ChildFieldInterface;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\Html;
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

    public static function getInputTemplatePath(): string
    {
        return 'fields/single-line-text';
    }

    public static function getReferenceBlockTemplatePath(): string
    {
        return 'fields/single-line-text';
    }


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

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $tag = parent::defineFieldSlotTag($key, $context);

        if ($tag && $key === 'fieldInput') {
            $tag->mergeCoreAttributes([
                'autocomplete' => 'postal-code',
                'data-zip' => true,
            ]);
        }

        return $tag;
    }
}
