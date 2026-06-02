<?php
namespace verbb\formie\base;

use verbb\formie\base\Field;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\fields\definitions\FieldClientChildren;
use verbb\formie\fields\values\FieldValueInterface;
use verbb\formie\gql\resolvers\elements\NestedFieldRowResolver;
use verbb\formie\gql\types\generators\NestedFieldGenerator;
use verbb\formie\gql\types\input\GroupInputType;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\ValidationHelper;
use verbb\formie\models\SlotTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Html;
use craft\helpers\Gql;
use craft\helpers\Template;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

abstract class ContainerParentField extends ParentField implements ParentFieldInterface
{
    // Constants
    // =========================================================================

    private const NESTED_KEY_UID = 'uid';
    private const NESTED_KEY_HANDLE = 'handle';


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
        foreach ($this->getFields() as $field) {
            $value = $element->getFieldValue($field->valueKey());

            // No need to validate if the field is conditionally hidden or disabled
            if ($field->isConditionallyHidden($element) || $field->getIsDisabled()) {
                continue;
            }

            ValidationHelper::validateField($element, $field, $value, ValidationHelper::fieldValidationAttribute($field));
        }
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof FieldValueInterface) {
            $value = $value->toValueArray();
        }

        if (!is_array($value)) {
            $value = [];
        }

        // Normalize all inner fields
        $values = [];

        foreach ($this->getFields() as $field) {
            // Get the value from the field's UID (database) or it's handle (POST)
            $fieldValue = $value[$field->uid] ?? $value[$field->handle] ?? null;

            $values[$field->handle] = $field->normalizeValue($fieldValue, $element);
        }

        return $values;
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->serializeNestedFieldValues($value, $element, self::NESTED_KEY_UID);
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

    protected function defineClientChildren(): FieldClientChildren
    {
        return FieldClientChildren::make(FieldClientChildren::MODEL_CONTAINER_PARENT)
            ->withChildren(FieldClientChildren::MODE_PARTS)
            ->withPartFieldResolver(fn() => $this->getFields(false));
    }

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        $values = [];

        foreach ($this->getEnabledFields($element) as $field) {
            $subValue = $element->getFieldValue($field->valueKey());
            $valueAsString = $field->getValueAsString($subValue, $element);

            if ($valueAsString) {
                $values[] = $valueAsString;
            }
        }

        return implode(', ', $values);
    }

    protected function defineValueAsArray(mixed $value, ElementInterface $element = null): mixed
    {
        $values = [];

        foreach ($this->getEnabledFields($element) as $field) {
            $subValue = $element->getFieldValue($field->valueKey());
            $valueAsArray = $field->getValueAsArray($subValue, $element);

            if (
                is_array($valueAsArray)
                && count($valueAsArray) === 1
                && array_key_exists(0, $valueAsArray)
                && !$field->supportsArrayValue()
            ) {
                $valueAsArray = $valueAsArray[0];
            }

            if ($valueAsArray) {
                $values[$field->handle] = $valueAsArray;
            }
        }

        return $values;
    }

    protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed
    {
        if ($this->isValueEmpty($value, $element)) {
            return [];
        }

        $values = [];

        foreach ($this->getEnabledFields($element) as $field) {
            $subValue = $element->getFieldValue($field->valueKey());
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

    protected function serializeNestedFieldValues(mixed $value, ?ElementInterface $element, string $keyBy): array
    {
        if ($value instanceof FieldValueInterface) {
            $value = $value->toValueArray();
        }

        if (!is_array($value)) {
            $value = [];
        }

        $values = [];

        foreach ($this->getFields() as $field) {
            // Accept either stored UID keys or incoming handle keys as input.
            $fieldValue = $value[$field->uid] ?? $value[$field->handle] ?? null;
            $targetKey = $keyBy === self::NESTED_KEY_HANDLE ? $field->handle : $field->uid;
            $values[$targetKey] = $field->serializeValue($fieldValue, $element);
        }

        return $values;
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): mixed
    {
        if ($this->isValueEmpty($value, $element)) {
            return '';
        }

        $values = '';

        foreach ($this->getVisibleEnabledFields($element) as $field) {
            $subValue = $element->getFieldValue($field->valueKey());
            $summary = $field->getValueForSummary($subValue, $element);
            $summaryHtml = $summary instanceof \Twig\Markup ? (string)$summary : Html::encode((string)$summary);

            $values .= Html::tag('strong', $field->label) . ' ' . $summaryHtml . Html::tag('br');
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
            $subValue = $element->getFieldValue("{$this->valueKey()}.$subFieldHandle");

            return $subField->getValueForIntegration($subValue, $integrationField, $integration, $element, $subFieldKey);
        }

        // Fetch the default handling
        return parent::defineValueForIntegration($value, $integrationField, $integration, $element, $fieldKey);
    }

    protected function defineValueForCondition(mixed $value, Submission $submission): mixed
    {
        return $this->serializeNestedFieldValues($value, $submission, self::NESTED_KEY_HANDLE);
    }

}
