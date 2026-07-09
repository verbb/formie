<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\ChildFieldInterface;
use verbb\formie\fields\Number;
use verbb\formie\fields\subfields\traits\DateSubFieldValueTrait;
use verbb\formie\helpers\ValidationMessagesHelper;

use craft\base\ElementInterface;
use craft\helpers\StringHelper;

class DateNumber extends Number implements ChildFieldInterface
{
    // Traits
    // =========================================================================
    
    use DateSubFieldValueTrait;
    
    
    // Public Methods
    // =========================================================================

    public function validateDateNumber(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->valueKey());
        $dateValue = $value;

        if (is_object($dateValue) && method_exists($dateValue, '__toString')) {
            $dateValue = (string)$dateValue;
        }

        if ($dateValue === null || $dateValue === '') {
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_INVALID));

            return;
        }

        if ($this->_isNotNumber($dateValue)) {
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_INVALID));

            return;
        }

        $dateValue = (int)$dateValue;

        if (!preg_match('/^[+-]?\d+$/', StringHelper::normalizeNumber($dateValue))) {
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_INVALID));
        }

        if ($this->min && $dateValue < $this->min) {
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_NUMBER_MIN, [
                'min' => $this->min,
            ]));
        }

        if ($this->max && $dateValue > $this->max) {
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_NUMBER_MAX, [
                'max' => $this->max,
            ]));
        }
    }

    public function getElementValidationRules(): array
    {
        // Child date number fields validate only their own scalar part value.
        $rules = [];
        $rules[] = [$this->handle, 'validateDateNumber'];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _isNotNumber($value)
    {
        return is_array($value) || is_bool($value) || (is_object($value) && !method_exists($value, '__toString')) || (!is_object($value) && !is_scalar($value) && $value !== null);
    }
}
