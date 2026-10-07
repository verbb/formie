<?php
namespace verbb\formie\base;

use verbb\formie\conditions\ConditionVisibility;
use verbb\formie\elements\Submission;
use verbb\formie\fields\definitions\FieldClientRenderedChildren;
use verbb\formie\fields\definitions\FieldValueType;
use verbb\formie\helpers\ValidationHelper;
use verbb\formie\models\IntegrationField;

use craft\base\Element;
use craft\base\ElementInterface;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Template;

use Twig\Markup;

abstract class ContainerParentField extends ParentField implements ParentFieldInterface
{
    // Constants
    // =========================================================================

    private const NESTED_KEY_UID = 'uid';
    private const NESTED_KEY_HANDLE = 'handle';


    // Public Methods
    // =========================================================================

    public function serializeValueForClientInput(mixed $value, ?ElementInterface $element = null): mixed
    {
        return $this->projectChildValues($value, $element, fn($field, $child) => $field->serializeValueForClientInput($field->normalizeFieldValue($child, $element), $element));
    }

    public function mergePartialRequestValue(mixed $previous, mixed $incoming): mixed
    {
        if (!is_array($incoming) || $incoming === []) {
            return $incoming;
        }
        $previous = $this->nestedValueParts($previous);
        $result = [];

        foreach ($this->getFields() as $field) {
            $prior = $previous[$field->handle] ?? null;
            $key = array_key_exists($field->uid, $incoming) ? $field->uid : $field->handle;
            $result[$field->handle] = array_key_exists($key, $incoming)
                ? $field->mergePartialRequestValue($prior, $incoming[$key])
                : $prior;
        }
        return $result;
    }

    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
    {
        $parts = $this->projectChildValues($value, $element, fn($field, $child) => $field->normalizeValueFromRequest($child, $element));
        return $this->normalizeValue($parts, $element);
    }

    public function decodeValueFromStorage(mixed $value): mixed
    {
        $value = parent::decodeValueFromStorage($value);

        if (!is_array($value)) {
            $value = Json::decodeIfJson($value);
        }

        // Keep scalar single Name values intact. Fixed Date parts have their own storage shape.
        if (!is_array($value)) {
            return $value;
        }
        return $this->projectChildValues($value, null, fn($field, $child) => $field->decodeValueFromStorage($child));
    }

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
            if (ConditionVisibility::unavailable($field, $element) || $field->getIsDisabled()) {
                continue;
            }

            ValidationHelper::validateField($element, $field, $value, ValidationHelper::fieldValidationAttribute($field));
        }
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        $value = $this->nestedValueParts($value);

        // Normalize all inner fields
        $values = [];

        foreach ($this->getFields() as $field) {
            // Get the value from the field's UID (database) or it's handle (POST)
            $fieldValue = $value[$field->uid] ?? $value[$field->handle] ?? null;

            $values[$field->handle] = $field->normalizeFieldValue($fieldValue, $element);
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

    protected function defineValueType(): FieldValueType
    {
        return FieldValueType::array();
    }

    protected function projectChildValues(mixed $value, ?ElementInterface $element, callable $project): array
    {
        $value = $this->nestedValueParts($value);
        $rows = [$value];
        $result = [];

        foreach ($rows as $rowKey => $row) {
            $row = is_array($row) ? $row : [];
            $result[$rowKey] = [];

            foreach ($this->getFields() as $field) {
                if ($field->getIsCosmetic()) {
                    continue;
                }
                $child = $row[$field->uid] ?? $row[$field->handle] ?? null;
                $result[$rowKey][$field->handle] = $project($field, $child);
            }
        }
        return $result[0] ?? [];
    }

    protected function defineValueForDb(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->serializeNestedFieldValues($value, $element, self::NESTED_KEY_UID);
    }

    protected function defineClientRenderedChildren(): FieldClientRenderedChildren
    {
        return FieldClientRenderedChildren::make(FieldClientRenderedChildren::MODEL_CONTAINER_PARENT)
            ->withChildren(FieldClientRenderedChildren::MODE_PARTS)
            ->withPartFieldResolver(fn() => $this->getEnabledFields());
    }

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        $values = [];

        foreach ($this->getEnabledFields($element) as $field) {
            $parts = $this->nestedValueParts($value);
            $subValue = $field->normalizeFieldValue($parts[$field->handle] ?? $parts[$field->uid] ?? null, $element);
            $valueAsString = $field->getValueAsString($subValue, $element);

            if ($valueAsString !== '') {
                $values[] = $valueAsString;
            }
        }

        return implode(', ', $values);
    }

    protected function defineValueAsData(mixed $value, ElementInterface $element = null): mixed
    {
        return $this->projectChildValues($value, $element, fn($field, $child) => $field->getValueAsData($field->normalizeFieldValue($child, $element), $element));
    }

    protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed
    {
        if ($this->isValueEmpty($value, $element)) {
            return [];
        }

        $values = [];

        foreach ($this->getEnabledFields($element) as $field) {
            $parts = $this->nestedValueParts($value);
            $subValue = $field->normalizeFieldValue($parts[$field->handle] ?? $parts[$field->uid] ?? null, $element);
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
        $value = $this->nestedValueParts($value);

        $values = [];

        foreach ($this->getFields() as $field) {
            // Accept either stored UID keys or incoming handle keys as input.
            $fieldValue = $value[$field->uid] ?? $value[$field->handle] ?? null;
            $targetKey = $keyBy === self::NESTED_KEY_HANDLE ? $field->handle : $field->uid;
            $values[$targetKey] = $field->serializeValueForDb($field->normalizeFieldValue($fieldValue, $element), $element);
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
            $parts = $this->nestedValueParts($value);
            $subValue = $field->normalizeFieldValue($parts[$field->handle] ?? $parts[$field->uid] ?? null, $element);
            $summary = $field->getValueForSummary($subValue, $element);
            $summaryHtml = $summary instanceof Markup ? (string)$summary : Html::encode((string)$summary);

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
            $parts = $this->nestedValueParts($value);
            $subValue = $subField->normalizeFieldValue($parts[$subField->handle] ?? $parts[$subField->uid] ?? null, $element);

            return $subField->getValueForIntegration($subValue, $integrationField, $integration, $element, $subFieldKey);
        }

        // Fetch the default handling
        return parent::defineValueForIntegration($value, $integrationField, $integration, $element, $fieldKey);
    }

    protected function defineValueForCondition(mixed $value, Submission $submission): mixed
    {
        return $this->projectChildValues($value, $submission, fn($field, $child) => $field->getValueForCondition($field->normalizeFieldValue($child, $submission), $submission));
    }

    protected function nestedValueParts(mixed $value): array
    {
        // Fixed parent fields with domain objects opt into their intrinsic
        // parts explicitly; generic parents never infer a transport shape.
        return is_array($value) ? $value : [];
    }
}
