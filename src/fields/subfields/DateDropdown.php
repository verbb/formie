<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\SubFieldInnerFieldInterface;
use verbb\formie\fields\Dropdown;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\base\ElementInterface;

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
                $range[] = (string)$option['value'];
            }
        }

        if (!in_array($value->format($this->validationFormatParam), $range)) {
            $element->addError($this->fieldKey, Craft::t('formie', '{attribute} is invalid.', ['attribute' => $this->label]));
        }
    }

    public function getElementValidationRules(): array
    {
        // Hacky way to handle Date/Time fields, until we refactor with a new `DateTimeModel`. The value used for
        // dropdown/input fields are a full DateTime, which won't work with `submission->getFieldValue()`
        
        // Remove any parent rules
        $rules = [];
        $rules[] = [$this->handle, 'validateDateRange'];

        return $rules;
    }
}
