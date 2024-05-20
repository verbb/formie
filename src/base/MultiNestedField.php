<?php
namespace verbb\formie\base;

use verbb\formie\base\Field;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\MultiNestedFieldInterface;
use verbb\formie\base\MultiNestedField;
use verbb\formie\gql\interfaces\RowInterface;
use verbb\formie\gql\types\input\RepeaterInputType;
use verbb\formie\gql\types\RowType;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\validators\ArrayValidator;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

use Throwable;

abstract class MultiNestedField extends NestedField implements MultiNestedFieldInterface
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
        $value = $element->getFieldValue($this->fieldKey);

        if ($element->getScenario() === Element::SCENARIO_LIVE && ($this->minRows || $this->maxRows)) {
            $arrayValidator = new ArrayValidator([
                'min' => $this->minRows ?: null,
                'max' => $this->maxRows ?: null,
                'tooFew' => $this->minRows ? Craft::t('app', '{attribute} should contain at least {min, number} {min, plural, one{block} other{blocks}}.', [
                    'attribute' => Craft::t('formie', $this->label),
                    'min' => $this->minRows, // Need to pass this in now
                ]) : null,
                'tooMany' => $this->maxRows ? Craft::t('app', '{attribute} should contain at most {max, number} {max, plural, one{block} other{blocks}}.', [
                    'attribute' => Craft::t('formie', $this->label),
                    'max' => $this->maxRows, // Need to pass this in now
                ]) : null,
                'skipOnEmpty' => false,
            ]);

            if (!$arrayValidator->validate($value, $error)) {
                $element->addError($this->fieldKey, $error);
            }
        }

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields() as $field) {
                // Ensure that the inner fields know about this specific block, to handle getting values properly
                $field->setParentField($this, $rowKey);

                $fieldKey = "$this->handle.$rowKey.$field->handle";
                $subValue = $element->getFieldValue($fieldKey);
                $isEmpty = $field->isValueEmpty($subValue, $element);

                // No need to validate if the field is conditionally hidden or disabled
                if ($field->isConditionallyHidden($element) || !$field->enabled) {
                    continue;
                }

                // Roll our own validation, due to lack of field layout and elements
                if ($field->required && $isEmpty) {
                    $element->addError($fieldKey, Craft::t('formie', '{attribute} cannot be blank.', ['attribute' => $field->label]));
                }

                foreach ($field->getElementValidationRules() as $rule) {
                    $this->normalizeFieldValidator($fieldKey, $rule, $field, $element, $isEmpty);
                }
            }
        }
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if (!is_array($value)) {
            $value = [];
        }

        // When set via GQL mutation
        if (isset($value['rows'])) {
            $value = $value['rows'];
        }

        // Normalize all inner fields
        $values = [];

        foreach ($this->getFields() as $field) {
            foreach ($value as $rowKey => $row) {
                // Get the value from the field's UID (database) or it's handle (POST)
                $fieldValue = $row[$field->uid] ?? $row[$field->handle] ?? null;
                
                // Ensure that the inner fields know about this specific block, to handle getting values properly
                $field->setParentField($this, $rowKey);

                $values[$rowKey][$field->handle] = $field->normalizeValue($fieldValue, $element);
            }
        }

        return $values;
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if (!is_array($value)) {
            $value = [];
        }

        // Serialize all inner fields
        $values = [];

        foreach ($this->getFields() as $field) {
            foreach ($value as $rowKey => $row) {
                // Get the value from the field's UID (database) or it's handle (POST)
                $fieldValue = $row[$field->uid] ?? $row[$field->handle] ?? null;

                // Ensure that the inner fields know about this specific block, to handle getting values properly
                $field->setParentField($this, $rowKey);

                $values[$rowKey][$field->uid] = $field->serializeValue($fieldValue, $element);
            }
        }

        // Reset any `new1` or `row1` keys
        $values = array_values($values);

        return $values;
    }

    public function beforeElementSave(ElementInterface $element, bool $isNew): bool
    {
        $hasErrors = false;

        $value = $element->getFieldValue($this->fieldKey);

        // Treat this field like an element, where we should trigger saving for each block and field
        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields() as $field) {
                // Ensure that the inner fields know about this specific block, to handle getting values properly
                $field->setParentField($this, $rowKey);

                if (!$field->beforeElementSave($element, $isNew)) {
                    $hasErrors = true;
                }
            }
        }

        return !$hasErrors;
    }

    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        $value = $element->getFieldValue($this->fieldKey);

        // Treat this field like an element, where we should trigger saving for each block and field
        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields() as $field) {
                // Ensure that the inner fields know about this specific block, to handle getting values properly
                $field->setParentField($this, $rowKey);

                $field->afterElementSave($element, $isNew);
            }
        }
    }
    

    // Protected Methods
    // =========================================================================

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        $values = [];

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields() as $field) {
                // Ensure that the inner fields know about this specific block, to handle getting values properly
                $field->setParentField($this, $rowKey);

                $subValue = $element->getFieldValue("$this->handle.$rowKey.$field->handle");
                $valueAsString = $field->getValueAsString($subValue, $element);

                if ($valueAsString) {
                    $values[] = $valueAsString;
                }
            }
        }

        return implode(', ', $values);
    }

    protected function defineValueAsJson(mixed $value, ElementInterface $element = null): mixed
    {
        $values = [];

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields() as $field) {
                // Ensure that the inner fields know about this specific block, to handle getting values properly
                $field->setParentField($this, $rowKey);

                $subValue = $element->getFieldValue("$this->handle.$rowKey.$field->handle");
                $valueAsJson = $field->getValueAsJson($subValue, $element);

                if ($valueAsJson) {
                    $values[$rowKey][$field->handle] = $valueAsJson;
                }
            }
        }

        return $values;
    }

    protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed
    {
        $values = [];

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields() as $field) {
                // Ensure that the inner fields know about this specific block, to handle getting values properly
                $field->setParentField($this, $rowKey);

                $subValue = $element->getFieldValue("$this->handle.$rowKey.$field->handle");
                $valueForExport = $field->getValueForExport($subValue, $element);

                $key = $this->getExportLabel($element) . ': ' . ($rowKey + 1);

                if (is_array($valueForExport)) {
                    foreach ($valueForExport as $i => $j) {
                        $values[$key . ': ' . $i] = $j;
                    }
                } else {
                    $values[$key . ': ' . $field->getExportLabel($element)] = $valueForExport;
                }
            }
        }

        return $values;
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): string
    {
        $values = '';

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields() as $field) {
                // Ensure that the inner fields know about this specific block, to handle getting values properly
                $field->setParentField($this, $rowKey);
                
                if ($field->getIsCosmetic() || $field->getIsHidden() || $field->isConditionallyHidden($element)) {
                    continue;
                }

                $subValue = $element->getFieldValue("$this->handle.$rowKey.$field->handle");
                $html = $field->getValueForSummary($subValue, $element);

                $values .= '<strong>' . $field->label . '</strong> ' . $html . '<br>';
            }
        }

        return Template::raw($values);
    }

}
