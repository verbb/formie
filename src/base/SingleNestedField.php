<?php
namespace verbb\formie\base;

use verbb\formie\base\Field;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\SingleNestedField;
use verbb\formie\base\SingleNestedFieldInterface;
use verbb\formie\gql\resolvers\elements\NestedFieldRowResolver;
use verbb\formie\gql\types\generators\NestedFieldGenerator;
use verbb\formie\gql\types\input\GroupInputType;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Model;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql;
use craft\helpers\Template;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

use yii\validators\RequiredValidator;
use yii\validators\Validator;

abstract class SingleNestedField extends NestedField implements SingleNestedFieldInterface
{
    // Public Methods
    // =========================================================================

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        $rules[] = [
            'validateBlocks',
            'on' => [Element::SCENARIO_ESSENTIALS, Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE],
            'skipOnEmpty' => false,
        ];

        return $rules;
    }

    public function validateBlocks(ElementInterface $element): void
    {
        $scenario = $element->getScenario();

        foreach ($this->getFields() as $field) {
            $value = $element->getFieldValue($field->fieldKey);

            // No need to validate if the field is conditionally hidden or disabled
            if ($field->isConditionallyHidden($element) || $field->getIsDisabled()) {
                continue;
            }

            // Roll our own validation, due to lack of field layout and elements
            $attribute = 'field:' . $field->getErrorKey();
            $isEmpty = fn() => $field->isValueEmpty($value, $element);

            if ($scenario === Element::SCENARIO_LIVE && $field->required) {
                (new RequiredValidator(['isEmpty' => $isEmpty]))->validateAttribute($element, $attribute);
            }

            foreach ($field->getElementValidationRules() as $rule) {
                $validator = $this->normalizeFieldValidator($attribute, $rule, $field, $element, $isEmpty);

                if (in_array($scenario, $validator->on) || (empty($validator->on) && !in_array($scenario, $validator->except))) {
                    $validator->validateAttributes($element);
                }
            }
        }
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if (!is_array($value) && !$value instanceof Model) {
            $value = [];
        }

        // Normalize all inner fields
        $values = [];

        foreach ($this->getFields() as $field) {
            // Get the value from the field's UID (database) or it's handle (POST)
            $fieldValue = $value[$field->uid] ?? $value[$field->handle] ?? null;

            // Ensure that the inner fields know about this specific block, to handle getting values properly
            $field->setParentField($this);

            $values[$field->handle] = $field->normalizeValue($fieldValue, $element);
        }

        return $values;
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if (!is_array($value) && !$value instanceof Model) {
            $value = [];
        }

        // Serialize all inner fields
        $values = [];

        foreach ($this->getFields() as $field) {
            // Get the value from the field's UID (database) or it's handle (POST)
            $fieldValue = $value[$field->uid] ?? $value[$field->handle] ?? null;

            // Ensure that the inner fields know about this specific block, to handle getting values properly
            $field->setParentField($this);

            $values[$field->uid] = $field->serializeValue($fieldValue, $element);
        }

        return $values;
    }

    public function getValueForCondition(mixed $value, Submission $submission): mixed
    {
        // When comparing for conditions, ensure we aren't using the UIDs of nested fields yet, that's used in `serializeValue()`.
        if (!is_array($value) && !$value instanceof Model) {
            $value = [];
        }

        // Serialize all inner fields
        $values = [];

        foreach ($this->getFields() as $field) {
            // Get the value from the field's UID (database) or it's handle (POST)
            $fieldValue = $value[$field->uid] ?? $value[$field->handle] ?? null;

            // Ensure that the inner fields know about this specific block, to handle getting values properly
            $field->setParentField($this);

            $values[$field->handle] = $field->serializeValue($fieldValue, $submission);
        }

        return $values;
    }

    public function beforeElementSave(ElementInterface $element, bool $isNew): bool
    {
        $hasErrors = false;

        // Push any field events to nested fields
        foreach ($this->getFields() as $field) {
            if (!$field->beforeElementSave($element, $isNew)) {
                $hasErrors = true;
            }
        }

        return !$hasErrors;
    }

    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        // Push any field events to nested fields
        foreach ($this->getFields() as $field) {
            $field->afterElementSave($element, $isNew);
        }
    }


    // Protected Methods
    // =========================================================================

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        $values = [];

        foreach ($this->getEnabledFields($element) as $field) {
            $subValue = $element->getFieldValue($field->fieldKey);
            $valueAsString = $field->getValueAsString($subValue, $element);

            if ($valueAsString) {
                $values[] = $valueAsString;
            }
        }

        return implode(', ', $values);
    }

    protected function defineValueAsJson(mixed $value, ElementInterface $element = null): mixed
    {
        $values = [];

        foreach ($this->getEnabledFields($element) as $field) {
            $subValue = $element->getFieldValue($field->fieldKey);
            $valueAsJson = $field->getValueAsJson($subValue, $element);

            if ($valueAsJson) {
                $values[$field->handle] = $valueAsJson;
            }
        }

        return $values;
    }

    protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed
    {
        $values = [];

        foreach ($this->getEnabledFields($element) as $field) {
            $subValue = $element->getFieldValue($field->fieldKey);
            $valueForExport = $field->getValueForExport($subValue, $element);

            $key = $this->getExportLabel($element);

            if (is_array($valueForExport)) {
                foreach ($valueForExport as $i => $j) {
                    $values[$key . ': ' . $i] = $j;
                }
            } else {
                $values[$key . ': ' . $field->getExportLabel($element)] = $valueForExport;
            }
        }

        return $values;
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): string
    {
        $values = '';

        foreach ($this->getVisibleEnabledFields($element) as $field) {
            $subValue = $element->getFieldValue($field->fieldKey);
            $html = $field->getValueForSummary($subValue, $element);

            $values .= '<strong>' . $field->label . '</strong> ' . $html . '<br>';
        }

        return Template::raw($values);
    }

    protected function defineValueForIntegration(mixed $value, IntegrationField $integrationField, IntegrationInterface $integration, ElementInterface $element = null, string $fieldKey = ''): mixed
    {
        // Check if we're trying to get a sub-field value
        if ($fieldKey) {
            $subFieldKey = explode('.', $fieldKey);
            $subFieldHandle = array_shift($subFieldKey);
            $subFieldKey = implode('.', $subFieldKey);

            $subField = $this->getFieldByHandle($subFieldHandle);
            $subValue = $element->getFieldValue("$this->fieldKey.$subFieldHandle");

            return $subField->getValueForIntegration($subValue, $integrationField, $integration, $element, $subFieldKey);
        }

        // Fetch the default handling
        return parent::defineValueForIntegration($value, $integrationField, $integration, $element, $fieldKey);
    }

}
