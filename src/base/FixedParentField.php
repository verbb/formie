<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyNestedFieldLayoutEvent;
use verbb\formie\fields\definitions\FieldClientChildren;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;

use GraphQL\Type\Definition\Type;

abstract class FixedParentField extends ContainerParentField implements FixedParentFieldInterface
{
    // Constants
    // =========================================================================

    protected const EXCLUDED_SUBFIELD_SETTING_NAMES = [
        'matchField',
        'includeInEmailFieldSummaries',
        'includeInEmail',
        'uniqueValue',
        'handle',
        'enableContentEncryption',
    ];


    // Properties
    // =========================================================================

    public ?string $subFieldLabelPosition = null;
    private ?array $_subFields = null;
    private array $_nestedEditorSchemaByTypeCache = [];


    // Public Methods
    // =========================================================================

    public function hasFieldLayout(): bool
    {
        return true;
    }

    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'subFieldLabelPosition';

        return $attributes;
    }

    public function getSubFields(): array
    {
        if ($this->_subFields !== null) {
            return $this->_subFields;
        }

        $this->_subFields = $this->defineSubFields();

        return $this->_subFields;
    }

    public function getDefaultFieldLayout(): FieldLayout
    {
        $fieldLayout = new FieldLayout();
        $fieldLayout->setPages([['rows' => $this->getSubFields()]]);

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
        $defaultRows = $this->getDefaultFieldLayout()->getFormBuilderConfig()[0]['rows'] ?? [];
        $settings['rows'] = $this->normalizeSubFieldRows($settings['rows'] ?? [], $defaultRows);

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

    public function getFieldTypeConfigData(): array
    {
        return array_merge(parent::getFieldTypeConfigData(), [
            'nestedLayoutBuilder' => $this->getNestedLayoutBuilderConfig(),
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

    protected function defineClientChildren(): FieldClientChildren
    {
        return FieldClientChildren::make(FieldClientChildren::MODEL_FIXED_PARENT)
            ->withChildren(FieldClientChildren::MODE_PARTS)
            ->withPartFieldResolver(fn() => $this->getFields(false));
    }

    protected function getNestedLayoutBuilderLayouts(): array
    {
        return [
            'rows' => $this->getSubFields(),
        ];
    }

    protected function getNestedLayoutBuilderPolicy(): array
    {
        return [
            'mode' => 'fixed',
            'operations' => [
                'allowAdd' => false,
                'allowRemove' => false,
                'allowReorder' => true,
                'allowMoveBetweenRows' => true,
            ],
            'immutableFieldKeys' => ['handle', 'type'],
            'immutableSettingsKeys' => ['handle', 'type'],
        ];
    }

    protected function getNestedLayoutBuilderAllowedFieldTypes(): array
    {
        $types = [];

        foreach ($this->getNestedLayoutBuilderLayouts() as $rows) {
            foreach (($rows ?: []) as $row) {
                foreach (($row['fields'] ?? []) as $field) {
                    $type = $field['type'] ?? null;

                    if ($type) {
                        $types[] = $type;
                    }
                }
            }
        }

        return array_values(array_unique($types));
    }

    protected function getNestedLayoutBuilderEditorSchemaByType(array $types): array
    {
        // Child sub-field schemas are loaded on demand when a sub-field edit modal opens.
        return [];
    }

    protected function sanitizeSubFieldEditorSchema(mixed $schema): mixed
    {
        if (!is_array($schema)) {
            return $schema;
        }

        if (array_is_list($schema)) {
            $nodes = [];

            foreach ($schema as $node) {
                $sanitized = $this->sanitizeSubFieldEditorSchema($node);

                if ($sanitized !== null) {
                    $nodes[] = $sanitized;
                }
            }

            return $nodes;
        }

        if (
            isset($schema['name'])
            && in_array($schema['name'], self::EXCLUDED_SUBFIELD_SETTING_NAMES, true)
        ) {
            return null;
        }

        if (
            isset($schema['$cmp'])
            && in_array($schema['$cmp'], ['ModalTabsTrigger', 'ModalTabsContent'], true)
            && (($schema['props']['value'] ?? null) === 'conditions')
        ) {
            return null;
        }

        if (
            isset($schema['fields'])
            && is_array($schema['fields'])
            && in_array('conditions', $schema['fields'], true)
        ) {
            return null;
        }

        $sanitized = [];

        foreach ($schema as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeSubFieldEditorSchema($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    protected function normalizeSubFieldRows(array $rows, array $defaultRows): array
    {
        if (!$rows) {
            return $defaultRows;
        }

        $defaultFieldsByHandle = [];
        $defaultFieldOrder = [];

        foreach ($defaultRows as $row) {
            foreach (($row['fields'] ?? []) as $field) {
                $handle = $field['settings']['handle'] ?? $field['handle'] ?? null;

                if (!$handle) {
                    continue;
                }

                $defaultFieldsByHandle[$handle] = $field;
                $defaultFieldOrder[] = $handle;
            }
        }

        $normalizedRows = [];
        $seenHandles = [];

        foreach ($rows as $row) {
            $normalizedFields = [];

            foreach (($row['fields'] ?? []) as $field) {
                $handle = $field['settings']['handle'] ?? $field['handle'] ?? null;

                if (!$handle || !isset($defaultFieldsByHandle[$handle]) || isset($seenHandles[$handle])) {
                    continue;
                }

                $seenHandles[$handle] = true;
                $normalizedFields[] = $field;
            }

            if ($normalizedFields) {
                $normalizedRows[] = [
                    ...$row,
                    'fields' => array_values($normalizedFields),
                ];
            }
        }

        foreach ($defaultFieldOrder as $handle) {
            if (isset($seenHandles[$handle])) {
                continue;
            }

            $normalizedRows[] = [
                'fields' => [$defaultFieldsByHandle[$handle]],
            ];
        }

        return array_values($normalizedRows);
    }

    protected function defineValueAsArray(mixed $value, ElementInterface $element = null): mixed
    {
        $values = parent::defineValueAsArray($value, $element);

        if (!is_array($values)) {
            return [];
        }

        foreach ($values as $key => $item) {
            if (is_array($item) && count($item) === 1 && array_key_exists(0, $item)) {
                $values[$key] = $item[0];
            }
        }

        return $values;
    }
}
