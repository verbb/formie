<?php
namespace verbb\formie\base;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;

trait SubFieldTrait
{
    // Properties
    // =========================================================================

    public ?string $subFieldLabelPosition = null;


    // Public Methods
    // =========================================================================

    public function hasSubFields(): bool
    {
        return true;
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();
        $rules[] = [$this->handle, 'validateRequiredFields', 'skipOnEmpty' => false];

        return $rules;
    }

    public function validateRequiredFields(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->fieldKey);
        $subFields = ArrayHelper::getColumn($this->getSubFieldOptions(), 'handle');

        foreach ($subFields as $subField) {
            $labelProp = "{$subField}Label";
            $enabledProp = "{$subField}Enabled";
            $requiredProp = "{$subField}Required";
            $fieldValue = $value->$subField ?? '';

            if ($this->$enabledProp && ($this->required || $this->$requiredProp) && StringHelper::isBlank($fieldValue)) {
                $element->addError($this->fieldKey . '.' . $subField, Craft::t('formie', '"{label}" cannot be blank.', [
                    'label' => $this->$labelProp,
                ]));
            }
        }
    }


    // Protected Methods
    // =========================================================================

    protected function defineValueForIntegration($value, $integrationField, $integration, ElementInterface $element = null, $fieldKey = ''): mixed
    {
        // Check if we're trying to get a subfield value
        if ($fieldKey) {
            // Override the value by fetching the value from the subfield. Override to ensure the default
            // handling typecasts the value correctly.
            $value = ArrayHelper::getValue($value, $fieldKey);
        }

        return parent::defineValueForIntegration($value, $integrationField, $integration, $element, $fieldKey);
    }
}
