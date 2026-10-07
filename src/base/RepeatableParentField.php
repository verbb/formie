<?php
namespace verbb\formie\base;

use verbb\formie\conditions\ConditionVisibility;
use verbb\formie\elements\Submission;
use verbb\formie\fields\definitions\FieldClientRenderedChildren;
use verbb\formie\fields\definitions\FieldValueType;
use verbb\formie\helpers\ValidationHelper;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\validators\ArrayValidator;

use Twig\Markup;

abstract class RepeatableParentField extends ParentField implements RepeatableParentFieldInterface
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
        $scenario = $element->getScenario();
        $value = $element->getFieldValue($this->valueKey());

        if ($scenario === Element::SCENARIO_LIVE && ($this->minRows || $this->maxRows)) {
            $arrayValidator = new ArrayValidator([
                'min' => $this->minRows ?: null,
                'max' => $this->maxRows ?: null,
                'tooFew' => $this->minRows ? Craft::t('app', '{attribute} should contain at least {min, number} {min, plural, one{block} other{blocks}}.', [
                    'attribute' => $this->label,
                    'min' => $this->minRows, // Need to pass this in now
                ]) : null,
                'tooMany' => $this->maxRows ? Craft::t('app', '{attribute} should contain at most {max, number} {max, plural, one{block} other{blocks}}.', [
                    'attribute' => $this->label,
                    'max' => $this->maxRows, // Need to pass this in now
                ]) : null,
                'skipOnEmpty' => false,
            ]);

            if (!$arrayValidator->validate($value, $error)) {
                $element->addError($this->valueKey(), $error);
            }
        }

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields($rowKey) as $field) {
                $fieldKey = "$this->handle.$rowKey.$field->handle";
                $subValue = $element->getFieldValue($fieldKey);

                // No need to validate if the field is conditionally hidden or disabled
                if (ConditionVisibility::unavailable($field, $element) || $field->getIsDisabled()) {
                    continue;
                }

                ValidationHelper::validateField($element, $field, $subValue, ValidationHelper::fieldValidationAttribute($field));
            }
        }
    }

    public function modifyAttributeLabels(array &$labels): void
    {
        // We need to factor in the error message key for Repeater blocks, but at this point we don't know what they are
        // so fudge it a little, and generate 70 label keys, and hope that people aren't making more than 70 rows.
        for ($i = 0; $i < 70; $i++) {
            foreach ($this->getFields($i) as $field) {
                $labels[$field->valueKey()] = $field->label;
            }
        }
    }

    public function serializeValueForClientInput(mixed $value, ?ElementInterface $element = null): mixed
    {
        return $this->projectChildValues($value, $element, fn($field, $child) => $field->serializeValueForClientInput($field->normalizeFieldValue($child, $element), $element));
    }

    public function mergePartialRequestValue(mixed $previous, mixed $incoming): mixed
    {
        if (!is_array($incoming) || $incoming === []) {
            return $incoming;
        }
        $previous = is_array($previous) ? $previous : [];
        $rows = $incoming['rows'] ?? $incoming;
        $result = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                $result[] = $row;
                continue;
            }
            $merged = [];

            foreach ($this->getFields($index) as $field) {
                $prior = $previous[$index][$field->handle] ?? null;
                $key = array_key_exists($field->uid, $row) ? $field->uid : $field->handle;
                $merged[$field->handle] = array_key_exists($key, $row)
                    ? $field->mergePartialRequestValue($prior, $row[$key])
                    : $prior;
            }
            $result[] = $merged;
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

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields($rowKey) as $field) {
                // Get the value from the field's UID (database) or it's handle (POST)
                $fieldValue = $row[$field->uid] ?? $row[$field->handle] ?? null;

                $values[$rowKey][$field->handle] = $field->normalizeFieldValue($fieldValue, $element);
            }
        }

        // Reset any `new1` or `row1` keys
        $values = array_values($values);

        return $values;
    }

    public function beforeElementSave(ElementInterface $element, bool $isNew): bool
    {
        $hasErrors = false;

        $value = $element->getFieldValue($this->valueKey());

        // Treat this field like an element, where we should trigger saving for each block and field
        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields($rowKey) as $field) {
                if (!$field->beforeElementSave($element, $isNew)) {
                    $hasErrors = true;
                }
            }
        }

        return !$hasErrors;
    }

    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        $value = $element->getFieldValue($this->valueKey());

        // Treat this field like an element, where we should trigger saving for each block and field
        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields($rowKey) as $field) {
                $field->afterElementSave($element, $isNew);
            }
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
        $value = is_array($value) ? $value : [];
        $rows = $value['rows'] ?? $value;
        $result = [];

        foreach ($rows as $rowKey => $row) {
            $row = is_array($row) ? $row : [];
            $result[$rowKey] = [];

            foreach ($this->getFields($rowKey) as $field) {
                if ($field->getIsCosmetic()) {
                    continue;
                }
                $child = $row[$field->uid] ?? $row[$field->handle] ?? null;
                $result[$rowKey][$field->handle] = $project($field, $child);
            }
        }
        return array_values($result);
    }

    protected function defineValueForDb(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->serializeNestedRows($value, $element, self::NESTED_KEY_UID);
    }

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        $values = [];

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields($rowKey) as $field) {
                if ($field->getIsCosmetic() || $field->getIsDisabled()) {
                    continue;
                }

                $subValue = $field->normalizeFieldValue($row[$field->handle] ?? $row[$field->uid] ?? null, $element);
                $valueAsString = $field->getValueAsString($subValue, $element);

                if ($valueAsString !== '') {
                    $values[] = $valueAsString;
                }
            }
        }

        return implode(', ', $values);
    }

    // Store each row through its contextual child fields and instance UIDs.
    protected function serializeNestedRows(mixed $value, ?ElementInterface $element, string $keyBy): array
    {
        if (!is_array($value)) {
            $value = [];
        }

        $values = [];

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields($rowKey) as $field) {
                // Accept either stored UID keys or incoming handle keys as input.
                $fieldValue = $row[$field->uid] ?? $row[$field->handle] ?? null;
                $targetKey = $keyBy === self::NESTED_KEY_HANDLE ? $field->handle : $field->uid;
                $values[$rowKey][$targetKey] = $field->serializeValueForDb($field->normalizeFieldValue($fieldValue, $element), $element);
            }
        }

        // Reset any `new1` or `row1` keys
        return array_values($values);
    }

    protected function defineValueAsData(mixed $value, ElementInterface $element = null): mixed
    {
        return $this->projectChildValues($value, $element, fn($field, $child) => $field->getValueAsData($field->normalizeFieldValue($child, $element), $element));
    }

    protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed
    {
        $values = [];

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields($rowKey) as $field) {
                if ($field->getIsCosmetic() || $field->getIsDisabled()) {
                    continue;
                }

                $subValue = $field->normalizeFieldValue($row[$field->handle] ?? $row[$field->uid] ?? null, $element);
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

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): mixed
    {
        $values = '';

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields($rowKey) as $field) {
                if ($field->getIsCosmetic() || $field->getIsHidden() || ConditionVisibility::unavailable($field, $element) || $field->getIsDisabled()) {
                    continue;
                }

                $subValue = $field->normalizeFieldValue($row[$field->handle] ?? $row[$field->uid] ?? null, $element);
                $summary = $field->getValueForSummary($subValue, $element);
                $summaryHtml = $summary instanceof Markup ? (string)$summary : Html::encode((string)$summary);

                $values .= Html::tag('strong', $field->label) . ' ' . $summaryHtml . Html::tag('br');
            }
        }

        return Template::raw($values);
    }

    protected function defineValueForCondition(mixed $value, Submission $submission): mixed
    {
        return $this->projectChildValues($value, $submission, fn($field, $child) => $field->getValueForCondition($field->normalizeFieldValue($child, $submission), $submission));
    }

    protected function defineClientRenderedChildren(): FieldClientRenderedChildren
    {
        return FieldClientRenderedChildren::make(FieldClientRenderedChildren::MODEL_REPEATABLE_PARENT)
            ->withChildren(FieldClientRenderedChildren::MODE_ROWS)
            ->withRowResolver(fn() => $this->getEnabledRows());
    }
}
