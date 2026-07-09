<?php
namespace verbb\formie\fields\subfields\traits;

use verbb\formie\elements\Submission;
use verbb\formie\fields\Date;
use verbb\formie\fields\values\DateFieldValue;
use verbb\formie\web\FieldRenderCallContext;

use craft\base\ElementInterface;

trait DateSubFieldValueTrait
{
    // Public Methods
    // =========================================================================

    public function getElementValue(?ElementInterface $element = null): mixed
    {
        if ($element instanceof Submission) {
            $value = $element->getFieldValue($this->valueKey());

            if (!$this->_isMissingDateSubFieldValue($value)) {
                return $value;
            }

            $parent = $this->getParentField();

            if ($parent instanceof Date) {
                $parentValue = $element->getFieldValue($parent->handle);

                if (!$this->_isMissingDateSubFieldValue($parentValue)) {
                    $partValue = $this->_resolveDateSubFieldDisplayValue($parent, $parentValue);

                    if (!$this->_isMissingDateSubFieldValue($partValue)) {
                        return $partValue;
                    }
                }
            }
        }

        return $this->getInitialValue($element);
    }

    public function getInitialValue(?ElementInterface $element = null): mixed
    {
        $prefillValue = $this->getPrefillValue($element, $found);

        if ($found) {
            $parent = $this->getParentField();

            if ($parent instanceof Date) {
                return $this->_resolveDateSubFieldDisplayValue($parent, $prefillValue) ?? '';
            }

            return $prefillValue;
        }

        $parent = $this->getParentField();

        if ($parent instanceof Date) {
            $defaultValue = $parent->getInitialValue($element);

            return $this->_resolveDateSubFieldDisplayValue($parent, $defaultValue) ?? '';
        }

        $defaultValue = $this->getDefaultValue();

        return $defaultValue ?? '';
    }


    // Private Methods
    // =========================================================================

    private function _resolveDateSubFieldDisplayValue(Date $parent, mixed $value): mixed
    {
        if ($this->_isMissingDateSubFieldValue($value)) {
            return null;
        }

        // Date picker datetime mode posts a single `datetime` input, so render the
        // combined parent value instead of the date-only sub-field projection.
        if (
            FieldRenderCallContext::get('inputName') === 'datetime'
            && $parent->getDisplayType() === 'datePicker'
            && $parent->getIsDateTime()
        ) {
            return $parent->getValueAsString($value, null);
        }

        return $parent->getSubFieldPartValue($value, $this->handle);
    }

    private function _isMissingDateSubFieldValue(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if ($value instanceof DateFieldValue) {
            return $value->isEmpty();
        }

        return false;
    }
}
