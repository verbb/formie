<?php
namespace verbb\formie\base;

use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\IntegrationField;

use Craft;
use craft\base\ElementInterface;

abstract class SubField extends Field implements SubFieldInterface
{
    // Properties
    // =========================================================================

    public ?string $subFieldLabelPosition = null;


    // Public Methods
    // =========================================================================

    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'subFieldLabelPosition';

        return $attributes;
    }

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
            $errorProp = "{$subField}ErrorMessage";
            $fieldValue = $value->$subField ?? '';

            $errorMessage = $this->$errorProp ?? '"{label}" cannot be blank.';

            if ($this->$enabledProp && ($this->required || $this->$requiredProp) && StringHelper::isBlank($fieldValue)) {
                $element->addError($this->fieldKey . '.' . $subField, Craft::t('formie', $errorMessage, [
                    'label' => $this->$labelProp,
                ]));
            }
        }
    }


    // Protected Methods
    // =========================================================================

    protected function defineValueForIntegration(mixed $value, IntegrationField $integrationField, IntegrationInterface $integration, ElementInterface $element = null, string $fieldKey = ''): mixed
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
