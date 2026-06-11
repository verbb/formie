<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface;
use verbb\formie\compatibility\fields\ParentFieldCompatibility;
use verbb\formie\events\ModifyNestedFieldLayoutEvent;
use verbb\formie\fields\MissingField;
use verbb\formie\gql\interfaces\FieldInterface as GqlFieldInterface;
use verbb\formie\gql\interfaces\RowInterface;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\query\NestedFieldQueryHelper;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\FieldLayoutRow;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field as CraftField;
use craft\base\FieldInterface as CraftFieldInterface;
use craft\db\Query;
use craft\elements\ElementCollection;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ElementHelper;
use craft\helpers\Template;
use craft\services\Elements;

use yii\db\ExpressionInterface;

use GraphQL\Type\Definition\Type;

abstract class ParentField extends Field implements ParentFieldInterface
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_NESTED_FIELD_LAYOUT = 'modifyNestedFieldLayout';


    // Traits
    // =========================================================================

    use ParentFieldCompatibility;


    // Static Methods
    // =========================================================================

    public static function queryCondition(array $instances, mixed $value, array &$params): array|string|ExpressionInterface|false|null
    {
        return NestedFieldQueryHelper::buildQueryCondition($instances, $value);
    }


    // Properties
    // =========================================================================

    public ?int $nestedLayoutId = null;

    private ?FieldLayout $_fieldLayout = null;
    private ?array $_nestedLayoutBuilderConfig = null;
    private ?array $_fieldsByHandle = null;


    // Public Methods
    // =========================================================================

    public function getIsRequired(): ?bool
    {
        // Nested fields themselves can't be required, only their inner fields can
        return null;
    }

    public function hasFieldLayout(): bool
    {
        return true;
    }

    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'nestedLayoutId';

        return $this->addCompatibilitySettingsAttributes($attributes);
    }

    public function getFormBuilderSettings(): array
    {
        $settings = parent::getFormBuilderSettings();

        // Return the form builder config for each row
        $settings['rows'] = $this->getFieldLayout()->getFormBuilderConfig()[0]['rows'] ?? [];

        return $settings;
    }

    public function getFieldTypeConfigData(): array
    {
        return array_merge(parent::getFieldTypeConfigData(), [
            'nestedLayoutBuilder' => $this->getNestedLayoutBuilderConfig(),
        ]);
    }

    public function getRows(bool $includeDisabled = true, string|int|null $rowKey = null): array
    {
        return array_map(
            fn(FieldLayoutRow $row) => $row->withParentField($this, $rowKey),
            $this->getFieldLayout()->getRows($includeDisabled),
        );
    }

    public function setRows(array $rows): void
    {
        foreach ($rows as $key => $row) {
            $rows[$key] = (!($row instanceof FieldLayoutRow)) ? new FieldLayoutRow($row) : $row;
        }

        // Set the rows for the field layout. There's only ever one page for nested fields, and there's always one page for a layout
        if ($pages = $this->getFieldLayout()->getPages()) {
            $pages[0]->setRows($rows);
        }

        $this->_nestedLayoutBuilderConfig = null;
        $this->_fieldsByHandle = null;
    }

    public function getFields(bool $includeDisabled = true, string|int|null $rowKey = null): array
    {
        $fields = [];

        foreach ($this->getRows($includeDisabled, $rowKey) as $row) {
            foreach ($row->getFields($includeDisabled) as $field) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    public function getFieldByHandle(string $handle): ?FieldInterface
    {
        if ($this->_fieldsByHandle === null) {
            // Nested-field helpers often probe by handle many times while resolving dot-paths,
            // projections, and query filters. Cache the flattened child lookup once so repeat
            // probes don't keep walking every nested row for the same parent field instance.
            $this->_fieldsByHandle = [];

            foreach ($this->getFields() as $field) {
                if (isset($field->handle)) {
                    $this->_fieldsByHandle[$field->handle] = $field;
                }
            }
        }

        return $this->_fieldsByHandle[$handle] ?? null;
    }

    public function getVisibleFields(ElementInterface $element = null): array
    {
        $fields = [];

        foreach ($this->getFields() as $field) {
            if ($field->getIsHidden() || $field->isConditionallyHidden($element) || $field->getIsDisabled()) {
                continue;
            }

            $fields[] = $field;
        }

        return $fields;
    }

    public function getEnabledFields(ElementInterface $element = null): array
    {
        $fields = [];

        foreach ($this->getFields() as $field) {
            if ($field->getIsCosmetic() || $field->getIsDisabled()) {
                continue;
            }

            $fields[] = $field;
        }

        return $fields;
    }

    public function getVisibleEnabledFields(ElementInterface $element = null): array
    {
        $fields = [];

        foreach ($this->getFields() as $field) {
            if ($field->getIsCosmetic() || $field->getIsHidden() || $field->isConditionallyHidden($element) || $field->getIsDisabled()) {
                continue;
            }

            $fields[] = $field;
        }

        return $fields;
    }

    public function getFieldLayout(): FieldLayout
    {
        if ($this->_fieldLayout) {
            return $this->_fieldLayout;
        }

        if (!$this->nestedLayoutId) {
            return $this->_fieldLayout = new FieldLayout();
        }

        $this->_fieldLayout = (Formie::$plugin->getFields()->getLayoutById($this->nestedLayoutId) ?? new FieldLayout());

        // Allow plugins to modify the field layout
        $event = new ModifyNestedFieldLayoutEvent([
            'fieldLayout' => $this->_fieldLayout,
        ]);

        $this->trigger(static::EVENT_MODIFY_NESTED_FIELD_LAYOUT, $event);

        return $this->_fieldLayout = $event->fieldLayout;
    }

    public function setFieldLayout(FieldLayout $fieldLayout): void
    {
        $this->_fieldLayout = $fieldLayout;
        $this->_nestedLayoutBuilderConfig = null;
    }

    public function validateFieldLayout(): void
    {
        if (!$this->hasFieldLayout()) {
            return;
        }

        $fieldLayout = $this->getFieldLayout();

        if (!$fieldLayout->validate()) {
            // Element models can't handle nested errors
            $errors = ArrayHelper::flatten($fieldLayout->getErrors());

            foreach ($errors as $errorKey => $error) {
                $this->addError($errorKey, $error);
            }
        }
    }

    public function beforeSave(bool $isNew): bool
    {
        // Ensure any parent validations run first
        if (!parent::beforeSave($isNew)) {
            return false;
        }

        $syncDefinitionId = $this->syncId ?: $this->fieldId;

        if ($isNew && $syncDefinitionId) {
            $definitionField = Formie::$plugin->getFields()->getFieldDefinitionById($syncDefinitionId);

            if ($definitionField instanceof self && $definitionField->nestedLayoutId) {
                // New synced instances must reuse the definition's nested layout. Saving the imported
                // builder rows here would generate fresh child UIDs and make older submissions unreadable.
                $this->nestedLayoutId = $definitionField->nestedLayoutId;
                $this->setFieldLayout($definitionField->getFieldLayout());

                return true;
            }
        }

        // Save the field layout as the last step - only if this has a field layout. Some SubFields opt-out
        if ($this->hasFieldLayout()) {
            if ($isNew) {
                // New nested fields must never re-use copied layout/field IDs from another field instance.
                $this->_clearLayoutIdentifiers($this->getFieldLayout());
                $this->nestedLayoutId = null;
            }

            if (!Formie::$plugin->getFields()->saveLayout($this->getFieldLayout())) {
                foreach ($this->getFieldLayout()->getPages() as $page) {
                    $errors = ArrayHelper::flatten($page->getErrors());

                    foreach ($errors as $errorKey => $error) {
                        $this->addError($errorKey, $error);
                    }
                }

                return false;
            }

            $this->nestedLayoutId = $this->getFieldLayout()->id;
        }

        return true;
    }

    public function afterDelete(): void
    {
        // Also delete the field layout and any fields
        if ($this->nestedLayoutId) {
            Formie::$plugin->getFields()->deleteLayoutById($this->nestedLayoutId);
        }
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'nestedRows' => [
                'name' => 'nestedRows',
                'type' => Type::listOf(RowInterface::getType()),
                'description' => 'The field’s nested rows.',
                'args' => [
                    'includeDisabled' => [
                        'name' => 'includeDisabled',
                        'description' => 'Whether to include fields with visibility "disabled".',
                        'type' => Type::boolean(),
                    ],
                ],
                'resolve' => function($source, $arguments) {
                    $includeDisabled = $arguments['includeDisabled'] ?? false;

                    return $source->getRows($includeDisabled);
                },
            ],
            'fields' => [
                'name' => 'fields',
                'type' => Type::listOf(GqlFieldInterface::getType()),
                'description' => 'The field’s nested fields.',
                'args' => [
                    'includeDisabled' => [
                        'name' => 'includeDisabled',
                        'description' => 'Whether to include fields with visibility "disabled".',
                        'type' => Type::boolean(),
                    ],
                ],
                'resolve' => function($source, $arguments) {
                    $includeDisabled = $arguments['includeDisabled'] ?? false;

                    return $source->getFields($includeDisabled);
                },
            ],
        ]);
    }

    public function getNestedFieldHandleUidMap(array $fields = null, string $handlePrefix = '', string $uidPrefix = ''): array
    {
        if ($fields === null) {
            // Fetch the top-level fields
            $fields = $this->getFields();
        }

        $fieldMap = [];

        foreach ($fields as $field) {
            $uid = $field->uid;
            $handle = $field->handle;
            
            // Prefix with dot-notation for nested fields
            $fullHandle = $handlePrefix ? $handlePrefix . '.' . $handle : $handle;
            $fullUid = $uidPrefix ? $uidPrefix . '.' . $uid : $uid;

            // Add the current field to the map
            $fieldMap[$fullHandle] = $fullUid;

            // If the field is an instance of ParentFieldInterface, recurse
            if ($field instanceof ParentFieldInterface) {
                $fieldMap += $this->getNestedFieldHandleUidMap($field->getFields(), $fullHandle, $fullUid);
            }
        }

        return $fieldMap;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['fieldLayout'], 'validateFieldLayout'];

        return $rules;
    }

    protected function getNestedLayoutBuilderConfig(): array
    {
        if ($this->_nestedLayoutBuilderConfig !== null) {
            return $this->_nestedLayoutBuilderConfig;
        }

        $layoutRowsMap = $this->getNestedLayoutBuilderLayouts();
        $layouts = [];
        $layoutTypes = [];

        foreach ($layoutRowsMap as $layoutKey => $rows) {
            $allowedHandles = [];
            $allowedTypes = [];

            foreach (($rows ?: []) as $row) {
                foreach (($row['fields'] ?? []) as $field) {
                    $handle = $field['handle'] ?? $field['settings']['handle'] ?? null;
                    $type = $field['type'] ?? null;

                    if ($handle) {
                        $allowedHandles[] = $handle;
                    }

                    if ($type) {
                        $allowedTypes[] = $type;
                        $layoutTypes[] = $type;
                    }
                }
            }

            $layouts[$layoutKey] = [
                'allowedHandles' => array_values(array_unique($allowedHandles)),
                'allowedTypes' => array_values(array_unique($allowedTypes)),
                'defaultRows' => $rows ?: [],
            ];
        }
        $allowedFieldTypes = $this->getNestedLayoutBuilderAllowedFieldTypes();
        $editorTypes = array_values(array_unique([...$layoutTypes, ...$allowedFieldTypes]));
        $editorSchemaByType = $this->getNestedLayoutBuilderEditorSchemaByType($editorTypes);

        $this->_nestedLayoutBuilderConfig = [
            'version' => 1,
            'layouts' => $layouts,
            'policy' => $this->getNestedLayoutBuilderPolicy(),
            'allowedFieldTypes' => $allowedFieldTypes,
            'editorSchemaByType' => $editorSchemaByType,
        ];

        return $this->_nestedLayoutBuilderConfig;
    }

    protected function getNestedLayoutBuilderLayouts(): array
    {
        return [
            'rows' => $this->getFieldLayout()->getFormBuilderConfig()[0]['rows'] ?? [],
        ];
    }

    protected function getNestedLayoutBuilderPolicy(): array
    {
        return [
            'mode' => 'flexible',
            'operations' => [
                'allowAdd' => true,
                'allowRemove' => true,
                'allowReorder' => true,
                'allowMoveBetweenRows' => true,
            ],
            'immutableFieldKeys' => [],
            'immutableSettingsKeys' => [],
        ];
    }

    protected function getNestedLayoutBuilderAllowedFieldTypes(): array
    {
        $registeredFieldTypes = Formie::$plugin->getFields()->getResolvedRegisteredFieldTypes();
        $fieldTypeDefinitions = Formie::$plugin->getFields()->getFieldTypeDefinitions($registeredFieldTypes);
        $allowedTypes = [];
        $disallowedTypes = $this->getNestedLayoutBuilderDisallowedFieldTypes();

        foreach ($fieldTypeDefinitions as $type => $fieldTypeDefinition) {
            if ($type === MissingField::class) {
                continue;
            }

            if (($fieldTypeDefinition['isChildField'] ?? false) === true) {
                continue;
            }

            if (in_array($type, $disallowedTypes, true)) {
                continue;
            }

            $allowedTypes[] = $type;
        }

        return array_values(array_unique($allowedTypes));
    }

    protected function getNestedLayoutBuilderDisallowedFieldTypes(): array
    {
        return [];
    }

    protected function getNestedLayoutBuilderEditorSchemaByType(array $types): array
    {
        return [];
    }

    protected function compileEditorSchemaForType(string $type, ?callable $schemaTransformer = null): ?array
    {
        $field = Formie::$plugin->getFields()->getRegisteredFieldByType($type);

        if (!$field) {
            return null;
        }

        $schema = $field->getFormBuilderSchema();

        if ($schemaTransformer) {
            $schema = $schemaTransformer($schema);
        }

        $compiledSchema = SchemaHelper::compileSchema($schema);

        return [
            'schema' => $compiledSchema['schema'],
            'schemaIndex' => $compiledSchema,
        ];
    }


    // Private Methods
    // =========================================================================

    private function _clearLayoutIdentifiers(FieldLayout $layout): void
    {
        $layout->id = null;
        $layout->uid = '';

        foreach ($layout->getPages() as $page) {
            $page->id = null;
            $page->layoutId = null;
            $page->uid = '';

            foreach ($page->getRows() as $row) {
                $row->id = null;
                $row->layoutId = null;
                $row->pageId = null;
                $row->uid = '';

                foreach ($row->getFields() as $field) {
                    $field->id = null;
                    $field->layoutId = null;
                    $field->pageId = null;
                    $field->rowId = null;
                    $field->uid = '';

                    if ($field instanceof NestedFieldInterface) {
                        $this->_clearLayoutIdentifiers($field->getFieldLayout());
                        $field->nestedLayoutId = null;
                    }
                }
            }
        }
    }

}
