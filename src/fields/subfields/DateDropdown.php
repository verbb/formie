<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\ChildFieldInterface;
use verbb\formie\fields\values\OptionValue;
use verbb\formie\fields\Dropdown;

use Craft;
use craft\base\ElementInterface;

class DateDropdown extends Dropdown implements ChildFieldInterface
{
    // Public Methods
    // =========================================================================

    public function validateDateRange(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->valueKey());

        $range = [];

        foreach ($this->options() as $option) {
            if (!isset($option['optgroup'])) {
                // Cast the option value to a string in case it is an integer
                $range[] = (string)$option['value'];
            }
        }

        $valueToValidate = $value instanceof OptionValue ? $value->value : $value;

        if ($valueToValidate === null || $valueToValidate === '' || !in_array((string)$valueToValidate, $range, true)) {
            $element->addError($this->valueKey(), Craft::t('formie', '{label} is invalid.', ['label' => $this->label]));
        }
    }

    public function getElementValidationRules(): array
    {
        // Child date dropdown fields validate only their own selected option value.
        $rules = [];
        $rules[] = [$this->handle, 'validateDateRange'];

        return $rules;
    }
}
