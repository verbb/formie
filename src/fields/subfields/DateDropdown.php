<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\SubFieldInnerFieldInterface;
use verbb\formie\fields\Dropdown;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\base\ElementInterface;

use DateTime;

class DateDropdown extends Dropdown implements SubFieldInnerFieldInterface
{
    // Public Methods
    // =========================================================================

    public function validateDateRange(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->fieldKey);

        $range = [];

        foreach ($this->options() as $option) {
            if (!isset($option['optgroup'])) {
                // Cast the option value to a string in case it is an integer
                $range[] = strtolower((string)$option['value']);
            }
        }

        if ($range && !in_array((string)$value, $range)) {
            $element->addError($this->fieldKey, Craft::t('formie', '{attribute} is invalid.', ['attribute' => $this->label]));
        }
    }

    public function getElementValidationRules(): array
    {
        // Remove any parent rules
        $rules = [];
        $rules[] = ['validateDateRange'];

        return $rules;
    }
}
