<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\SubFieldInnerFieldInterface;
use verbb\formie\fields\Number;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\StringHelper;

class DateNumber extends Number implements SubFieldInnerFieldInterface
{
    // Public Methods
    // =========================================================================

    public function validateDateNumber(ElementInterface $element): void
    {
        $dateValue = $element->getFieldValue($this->fieldKey);

        if ($this->_isNotNumber($dateValue)) {
            $element->addError($this->fieldKey, Craft::t('formie', '{attribute} is invalid.', ['attribute' => $this->label]));
            return;
        }

        if (!preg_match('/^[+-]?\d+$/', StringHelper::normalizeNumber($dateValue))) {
            $element->addError($this->fieldKey, Craft::t('formie', '{attribute} is invalid.', ['attribute' => $this->label]));
        }

        if ($this->min && $dateValue < $this->min) {
            $element->addError($this->fieldKey, Craft::t('formie', '{attribute} must be no less than {min}.', ['attribute' => $this->label, 'min' => $this->min]));
        }

        if ($this->max && $dateValue > $this->max) {
            $element->addError($this->fieldKey, Craft::t('formie', '{attribute} must be no greater than {max}.', ['attribute' => $this->label, 'max' => $this->max]));
        }
    }

    public function getElementValidationRules(): array
    {
        // Remove any parent rules
        $rules = [];
        $rules[] = ['validateDateNumber'];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _isNotNumber($value)
    {
        return is_array($value) || is_bool($value) || (is_object($value) && !method_exists($value, '__toString')) || (!is_object($value) && !is_scalar($value) && $value !== null);
    }
}
