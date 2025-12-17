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
        // Ensure that we're always dealing with the parent value (DateTime object)
        // and not trying to use dot-notation to get `DateTime.year` for example.
        // At least until we implement proper `DateTimeModel` support.
        $fieldKey = explode('.', $this->fieldKey);
        $handle = array_pop($fieldKey);
        $fieldKey = implode('.', $fieldKey);

        $value = $element->getFieldValue($fieldKey);

        $range = [];

        foreach ($this->options() as $option) {
            if (!isset($option['optgroup'])) {
                // Cast the option value to a string in case it is an integer
                $range[] = strtolower((string)$option['value']);
            }
        }

        if ($value instanceof DateTime && !in_array($value->format($this->validationFormatParam), $range)) {
            $element->addError($this->fieldKey, Craft::t('formie', '{attribute} is invalid.', ['attribute' => $this->label]));
        }
    }

    public function getElementValidationRules(): array
    {
        // Hacky way to handle Date/Time fields, until we refactor with a new `DateTimeModel`. The value used for
        // dropdown/input fields are a full DateTime, which won't work with `submission->getFieldValue()`
        
        // Remove any parent rules
        $rules = [];
        $rules[] = ['validateDateRange'];

        return $rules;
    }
}
