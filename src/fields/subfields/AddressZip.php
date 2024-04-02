<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\SubFieldInnerFieldInterface;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\base\ElementInterface;

class AddressZip extends SingleLineText implements SubFieldInnerFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Address - ZIP / Postal Code');
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

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();
        $rules[] = [$this->handle, 'validateZip'];

        return $rules;
    }

    public function validateZip(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->fieldKey);

        if (strlen($value) > 10) {
            $element->addError($this->fieldKey, Craft::t('formie', '"{label}" should contain at most {max, number} {max, plural, one{character} other{characters}}.', [
                'label' => $this->label,
                'max' => 10,
            ]));
        }
    }
}
