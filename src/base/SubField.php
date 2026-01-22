<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\events\ModifyNestedFieldLayoutEvent;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\FieldLayoutRow;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;

use GraphQL\Type\Definition\Type;

abstract class SubField extends SingleNestedField implements SubFieldInterface
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

    public function hasNestedFields(): bool
    {
        // Technically, Sub-Field _do_ have nested fields, but we want to differentiate Group/Repeater to Sub-Fields
        return false;
    }

    public function hasFieldLayout(): bool
    {
        return $this->hasSubFields();
    }

    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'subFieldLabelPosition';

        return $attributes;
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        foreach ($this->getFields() as $field) {
            // Ensure that we enforce content encryption on sub-fields from the parent
            $field->enableContentEncryption = $this->enableContentEncryption;
        }

        return parent::normalizeValue($value, $element);
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        foreach ($this->getFields() as $field) {
            // Ensure that we enforce content encryption on sub-fields from the parent
            $field->enableContentEncryption = $this->enableContentEncryption;
        }

        return parent::serializeValue($value, $element);
    }

    public function getSubFields(): array
    {
        return $this->defineSubFields();
    }

    public function getDefaultFieldLayout(): FieldLayout
    {
        $fieldLayout = new FieldLayout();
        $fieldLayout->id = $this->nestedLayoutId;
        $fieldLayout->setPages([['rows' => $this->getSubFields()]]);

        foreach ($fieldLayout->getPages() as $page) {
            $page->layoutId = $this->nestedLayoutId;

            foreach ($page->getRows() as $row) {
                $row->layoutId = $this->nestedLayoutId;

                foreach ($row->getFields() as $field) {
                    $field->layoutId = $this->nestedLayoutId;
                }
            }
        }

        // Allow plugins to modify the field layout
        $event = new ModifyNestedFieldLayoutEvent([
            'fieldLayout' => $fieldLayout,
        ]);

        $this->trigger(static::EVENT_MODIFY_NESTED_FIELD_LAYOUT, $event);

        return $event->fieldLayout;
    }

    public function getFormBuilderSettings(): array
    {
        $settings = parent::getFormBuilderSettings();

        // Generate rows for new fields
        $fieldLayout = $this->getDefaultFieldLayout();

        // If a brand-new field, load in the defaults
        if (!$settings['rows']) {
            // Return the form builder config for each row
            $settings['rows'] = $fieldLayout->getFormBuilderConfig()[0]['rows'] ?? [];
        } else {
            // Just in case we've modified the default fields, and they are missing from the saved layout for the field
            $defaultFields = ArrayHelper::getColumn($fieldLayout->getFields(), 'handle');
            $currentFields = ArrayHelper::getColumn($this->getFieldLayout()->getFields(), 'handle');

            // Remove any fields that are no longer in our sub-field layout definition
            foreach ($settings['rows'] as $rowKey => $row) {
                foreach ($row['fields'] as $fieldKey => $field) {
                    // Watch out for missing fields
                    $handle = $field['settings']['handle'] ?? null;

                    if (!$handle || !in_array($handle, $defaultFields)) {
                        // Remove the field
                        unset($settings['rows'][$rowKey]['fields'][$fieldKey]);
                    }
                }

                // Check if the row should be removed (empty field)
                if (!$settings['rows'][$rowKey]['fields']) {
                    unset($settings['rows'][$rowKey]);
                }
            }

            // Reset indexes to play nice with Vue
            $settings['rows'] = array_values($settings['rows']);

            // And the reverse - if any are in the sub-field layout definition, but not in the field layout
            $subFieldRows = $fieldLayout->getFormBuilderConfig()[0]['rows'] ?? [];

            foreach ($subFieldRows as $rowKey => $row) {
                foreach ($row['fields'] as $fieldKey => $field) {
                    // Watch out for missing fields
                    $handle = $field['settings']['handle'] ?? null;

                    if (!$handle || !in_array($handle, $currentFields)) {
                        // Insert the default field into the layout
                        $newRow = $row;
                        $newRow['fields'] = [$field];

                        // Try to add it in the original position, but as a new row, just in case there's collisions
                        array_splice($settings['rows'], $rowKey, 0, [$newRow]);
                    }
                }
            }
        }

        return $settings;
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'subFieldLabelPosition' => [
                'name' => 'subFieldLabelPosition',
                'type' => Type::string(),
            ],
        ]);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [
            ['subFieldLabelPosition'],
            'in',
            'range' => Formie::$plugin->getFields()->getLabelPositions(),
            'skipOnEmpty' => true,
        ];

        return $rules;
    }

    protected function defineSubFields(): array
    {
        return [];
    }

    protected function defineValueForVariable(mixed $value, Submission $submission, Notification $notification): mixed
    {
        $values = [
            '__toString' => StringHelper::toString($value),
        ];

        foreach ($this->getFields() as $subField) {
            $value = $submission->getFieldValue($subField->fieldKey);
            $fieldValues = Variables::getParsedFieldValue($subField, $value, $submission, $notification);

            if (is_array($fieldValues)) {
                foreach ($fieldValues as $key => $fieldValue) {
                    $values[$subField->handle][$key] = $fieldValue;
                }
            } else {
                $values[$subField->handle] = $fieldValues;
            }
        }

        return $values;
    }
}
