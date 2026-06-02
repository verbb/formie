<?php
namespace verbb\formie\base;

use verbb\formie\base\Field;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\RepeatableParentFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\definitions\FieldClientChildren;
use verbb\formie\gql\interfaces\RowInterface;
use verbb\formie\gql\types\input\RepeaterInputType;
use verbb\formie\gql\types\RowType;
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
use craft\helpers\Json;
use craft\helpers\Template;
use craft\validators\ArrayValidator;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

use Throwable;

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

    public function getFields(bool $includeDisabled = true, string|int|null $rowKey = null): array
    {
        return parent::getFields($includeDisabled, $rowKey);
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
                $element->addError($this->valueKey(), $error);
            }
        }

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields(true, $rowKey) as $field) {
                $fieldKey = "$this->handle.$rowKey.$field->handle";
                $subValue = $element->getFieldValue($fieldKey);

                // No need to validate if the field is conditionally hidden or disabled
                if ($field->isConditionallyHidden($element) || $field->getIsDisabled()) {
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
            foreach ($this->getFields(true, $i) as $field) {
                $labels[$field->valueKey()] = $field->label;
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

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields(true, $rowKey) as $field) {
                // Get the value from the field's UID (database) or it's handle (POST)
                $fieldValue = $row[$field->uid] ?? $row[$field->handle] ?? null;

                $values[$rowKey][$field->handle] = $field->normalizeValue($fieldValue, $element);
            }
        }

        // Reset any `new1` or `row1` keys
        $values = array_values($values);

        return $values;
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->serializeNestedRows($value, $element, self::NESTED_KEY_UID);
    }

    public function beforeElementSave(ElementInterface $element, bool $isNew): bool
    {
        $hasErrors = false;

        $value = $element->getFieldValue($this->valueKey());

        // Treat this field like an element, where we should trigger saving for each block and field
        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields(true, $rowKey) as $field) {
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
            foreach ($this->getFields(true, $rowKey) as $field) {
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
            foreach ($this->getFields(true, $rowKey) as $field) {
                if ($field->getIsCosmetic() || $field->getIsDisabled()) {
                    continue;
                }

                $subValue = $element->getFieldValue("$this->handle.$rowKey.$field->handle");
                $valueAsString = $field->getValueAsString($subValue, $element);

                if ($valueAsString) {
                    $values[] = $valueAsString;
                }
            }
        }

        return implode(', ', $values);
    }

    /**
     * Repeater storage and repeater condition subjects both serialize nested
     * child values; the only intentional difference is whether each row is
     * keyed by child UID (storage) or child handle (condition subjects).
     */
    protected function serializeNestedRows(mixed $value, ?ElementInterface $element, string $keyBy): array
    {
        if (!is_array($value)) {
            $value = [];
        }

        $values = [];

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields(true, $rowKey) as $field) {
                // Accept either stored UID keys or incoming handle keys as input.
                $fieldValue = $row[$field->uid] ?? $row[$field->handle] ?? null;
                $targetKey = $keyBy === self::NESTED_KEY_HANDLE ? $field->handle : $field->uid;
                $values[$rowKey][$targetKey] = $field->serializeValue($fieldValue, $element);
            }
        }

        // Reset any `new1` or `row1` keys
        return array_values($values);
    }

    protected function defineValueAsArray(mixed $value, ElementInterface $element = null): mixed
    {
        $values = [];

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields(true, $rowKey) as $field) {
                if ($field->getIsCosmetic() || $field->getIsDisabled()) {
                    continue;
                }

                $subValue = $element->getFieldValue("$this->handle.$rowKey.$field->handle");
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
                    $values[$rowKey][$field->handle] = $valueAsArray;
                }
            }
        }

        return $values;
    }

    protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed
    {
        $values = [];

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields(true, $rowKey) as $field) {
                if ($field->getIsCosmetic() || $field->getIsDisabled()) {
                    continue;
                }

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

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): mixed
    {
        $values = '';

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields(true, $rowKey) as $field) {
                if ($field->getIsCosmetic() || $field->getIsHidden() || $field->isConditionallyHidden($element) || $field->getIsDisabled()) {
                    continue;
                }

                $subValue = $element->getFieldValue("$this->handle.$rowKey.$field->handle");
                $summary = $field->getValueForSummary($subValue, $element);
                $summaryHtml = $summary instanceof \Twig\Markup ? (string)$summary : Html::encode((string)$summary);

                $values .= Html::tag('strong', $field->label) . ' ' . $summaryHtml . Html::tag('br');
            }
        }

        return Template::raw($values);
    }

    protected function defineValueForCondition(mixed $value, Submission $submission): mixed
    {
        return $this->serializeNestedRows($value, $submission, self::NESTED_KEY_HANDLE);
    }

    protected function defineClientChildren(): FieldClientChildren
    {
        return FieldClientChildren::make(FieldClientChildren::MODEL_REPEATABLE_PARENT)
            ->withChildren(FieldClientChildren::MODE_ROWS)
            ->withRowResolver(fn() => $this->getRows(false));
    }

}
