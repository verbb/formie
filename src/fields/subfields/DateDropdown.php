<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\ChildFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\subfields\traits\DateSubFieldValueTrait;
use verbb\formie\fields\values\OptionValue;
use verbb\formie\helpers\ValidationMessagesHelper;

use craft\base\ElementInterface;

class DateDropdown extends Dropdown implements ChildFieldInterface
{
    // Traits
    // =========================================================================

    use DateSubFieldValueTrait;


    // Public Methods
    // =========================================================================

    public function getFieldOptions(): array
    {
        $options = parent::getFieldOptions();

        // Without an empty choice, an untouched date part would submit its first option.
        // The generated empty row is a legacy `disabled` row, so option resolution hides it.
        if (!$this->placeholder) {
            array_unshift($options, ['label' => '', 'value' => '']);
        }

        return $options;
    }

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
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_INVALID));
        }
    }

    public function getElementValidationRules(): array
    {
        // Child date dropdown fields validate only their own selected option value.
        $rules = [];
        $rules[] = [$this->handle, 'validateDateRange'];

        return $rules;
    }


    // Protected Methods
    // =========================================================================

    protected function defineValueForCondition(mixed $value, Submission $submission): mixed
    {
        // Date parts are stored as plain values on the Date field, rather than as option values.
        return $value instanceof OptionValue ? $value->value : $value;
    }
}
