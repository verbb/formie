<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\Field;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\fields\values\ColorFieldValue;
use verbb\formie\fields\values\TableFieldValue;
use verbb\formie\fields\definitions\FieldClientModules;
use verbb\formie\fields\definitions\FieldValueClass;
use verbb\formie\gql\types\TableRowType;
use verbb\formie\gql\types\generators\KeyValueGenerator;
use verbb\formie\gql\types\generators\TableRowTypeGenerator;
use verbb\formie\models\ClientModule;
use verbb\formie\models\SlotTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;
use verbb\formie\positions\Hidden as HiddenPosition;
use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\fields\Table as CraftTable;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Component;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\validators\ArrayValidator;
use craft\validators\ColorValidator;
use craft\validators\HandleValidator;
use craft\validators\UrlValidator;
use craft\web\assets\timepicker\TimepickerAsset;

use DateTime;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

use yii\db\Schema;
use yii\validators\EmailValidator;

class Table extends Field
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Table');
    }

    public static function defineFieldType(): array
    {
        return array_merge(parent::defineFieldType(), [
            'isTableField' => true,
            'fieldKind' => self::KIND_TABLE,
        ]);
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/table/icon.svg';
    }

    public static function phpType(): string
    {
        return 'array|null';
    }

    public static function dbType(): string
    {
        return Schema::TYPE_JSON;
    }

    public static function supportsGqlConfigProvider(): bool
    {
        return true;
    }

    public static function gqlContentTypeFromConfig(array $config): Type|array
    {
        $typeName = Formie::$plugin->getFields()->getFieldConfigGqlTypeName($config, 'TableRow');

        $type = GqlEntityRegistry::getOrCreate($typeName, function() use ($config, $typeName) {
            $contentFields = TableRowType::prepareRowFieldDefinitionFromColumns(self::_getColumnsFromConfig($config));

            return new TableRowType([
                'name' => $typeName,
                'fields' => function() use ($contentFields, $typeName) {
                    return Craft::$app->getGql()->prepareFieldDefinitions($contentFields, $typeName);
                },
            ]);
        });

        return Type::listOf($type);
    }

    public static function gqlContentMutationArgumentTypeFromConfig(array $config): Type|array
    {
        $typeName = Formie::$plugin->getFields()->getFieldConfigGqlTypeName($config, 'TableRowInput');

        return Type::listOf(GqlEntityRegistry::getOrCreate($typeName, fn() => new InputObjectType([
            'name' => $typeName,
            'fields' => fn() => TableRowType::prepareRowFieldDefinitionFromColumns(self::_getColumnsFromConfig($config)),
        ])));
    }

    private static function _getColumnsFromConfig(array $config): array
    {
        $settings = Formie::$plugin->getFields()->getFieldConfigSettings($config);
        $columns = $settings['columns'] ?? [];

        return is_array($columns) ? $columns : [];
    }


    // Properties
    // =========================================================================

    public bool $staticRows = false;
    public ?string $addRowLabel = null;
    public ?int $maxRows = null;
    public ?int $minRows = null;
    public bool $static = false;

    public array $columns = [
        'col1' => [
            'heading' => '',
            'handle' => '',
            'type' => 'singleline',
        ],
    ];

    public ?array $defaults = [[]];


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Setuo defaults for some values which can't in in the property definition
        $config['addRowLabel'] = $config['addRowLabel'] ?? Craft::t('formie', 'Add a row');

        // Config normalization
        if (array_key_exists('columns', $config)) {
            if (!is_array($config['columns'])) {
                unset($config['columns']);
            } else {
                foreach ($config['columns'] as $colId => &$column) {
                    // If the column doesn't specify a type, then it probably wasn't meant to be submitted
                    if (!isset($column['type'])) {
                        unset($config['columns'][$colId]);
                        continue;
                    }

                    if ($column['type'] === 'select') {
                        if (!isset($column['options'])) {
                            $column['options'] = [];
                        } elseif (is_string($column['options'])) {
                            $column['options'] = Json::decode($column['options']);
                        }
                    } else {
                        unset($column['options']);
                    }
                }
                unset($column);
            }
        }

        if (isset($config['defaults'])) {
            if (!is_array($config['defaults'])) {
                $config['defaults'] = (!empty($config['id']) || $config['defaults'] === '') ? [] : [[]];
            } else {
                // Make sure the array is non-associative and with incrementing keys
                $config['defaults'] = array_values($config['defaults']);
            }
        }

        // Convert default date cell values to ISO8601 strings
        if (!empty($config['columns']) && isset($config['defaults'])) {
            foreach ($config['columns'] as $colId => $col) {
                if (in_array($col['type'], ['date', 'time'], true)) {
                    foreach ($config['defaults'] as &$row) {
                        if (isset($row[$colId])) {
                            $row[$colId] = DateTimeHelper::toIso8601($row[$colId]) ?: null;
                        }
                    }
                }
            }
        }

        parent::__construct($config);
    }

    public function fieldKind(): string
    {
        return self::KIND_TABLE;
    }

    public function init(): void
    {
        parent::init();

        if ($this->staticRows) {
            $this->minRows = null;
            $this->maxRows = null;
        }
    }

    public function validateColumns(): void
    {
        foreach ($this->columns as &$col) {
            if ($col['handle']) {
                $error = null;

                if (!preg_match('/^' . HandleValidator::$handlePattern . '$/', $col['handle'])) {
                    $error = Craft::t('formie', '“{handle}” isn’t a valid handle.', [
                        'handle' => $col['handle'],
                    ]);
                } elseif (preg_match('/^col\d+$/', $col['handle'])) {
                    $error = Craft::t('formie', 'Column handles can’t be in the format “{format}”.', [
                        'format' => 'colX',
                    ]);
                }

                if ($error) {
                    $col['handle'] = [
                        'value' => $col['handle'],
                        'hasErrors' => true,
                    ];

                    $this->addError('columns', $error);
                }
            }
        }
    }

    public function hasMinRows(): bool
    {
        return (bool)$this->minRows;
    }

    public function hasMaxRows(): bool
    {
        return (bool)$this->maxRows;
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewTable(),
        ];
    }

    public function getFormBuilderSettings(): array
    {
        $settings = parent::getFormBuilderSettings();

        // The CP builder edits table columns as an ordered row array with explicit IDs,
        // while Craft stores them as an associative config object keyed by column ID.

        // When validation has already reshaped some values, preserve the editable row shape
        // so the builder can continue editing the in-memory data cleanly.
        if (!$this->hasErrors()) {
            foreach ($settings['columns'] as $key => &$column) {
                $column['id'] = $key;
            }

            unset($column);

            $settings['columns'] = array_values($settings['columns']);
        } else {
            // Validation can sometimes hand values back in an array-like wrapper shape.
            // Collapse those back to scalars so the builder row editor receives clean cell values.
            foreach ($settings['columns'] as $colId => $column) {
                foreach ($column as $key => $col) {
                    if (is_array($col)) {
                        $settings['columns'][$colId][$key] = $col['value'] ?? '';
                    }
                }
            }
        }

        return $settings;
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();
        $rules[] = [$this->handle, 'validateTableData'];

        return $rules;
    }

    public function validateTableData(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->valueKey());

        if (!empty($value) && !empty($this->columns)) {
            foreach ($value as &$row) {
                foreach ($this->columns as $colId => $col) {
                    if (is_string($row[$colId])) {
                        // Trim the value before validating
                        $row[$colId] = trim($row[$colId]);
                    }

                    if (!$this->_validateCellValue($col['type'], $row[$colId], $error)) {
                        $element->addError($this->valueKey(), $error);
                    }
                }
            }
        }
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->_normalizeValueInternal($value, $element, false);
    }

    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element = null): mixed
    {
        return $this->_normalizeValueInternal($value, $element, true);
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if (!is_array($value) || empty($this->columns)) {
            return null;
        }

        $serialized = [];
        $supportsMb4 = Craft::$app->getDb()->getSupportsMb4();

        foreach ($value as $row) {
            $serializedRow = [];

            foreach ($this->columns as $colId => $column) {
                if ($column['type'] === 'heading') {
                    continue;
                }

                // Accept both persisted column IDs and client handle keys so
                // edited builder payloads and normalized row values round-trip
                // through the same serializer.
                $value = $row[$colId] ?? (($column['handle'] && array_key_exists($column['handle'], $row)) ? $row[$column['handle']] : null);
                $value = $this->_serializeCellValue($column['type'], $value);

                if (is_string($value) && !$supportsMb4) {
                    $value = StringHelper::emojiToShortcodes(StringHelper::escapeShortcodes($value));
                }

                $serializedRow[$colId] = parent::serializeValue($value ?? null, $element);
            }

            $serialized[] = $serializedRow;
        }

        return $serialized;
    }

    public function getContentGqlType(): Type|array
    {
        $type = TableRowTypeGenerator::generateType($this);

        return Type::listOf($type);
    }

    public function getContentGqlMutationArgumentType(): Type|array
    {
        $typeName = $this->handle . '_TableRowInput';

        return Type::listOf(GqlEntityRegistry::getOrCreate($typeName, fn() => new InputObjectType([
            'name' => $typeName,
            'fields' => fn() => TableRowType::prepareRowFieldDefinition($this),
        ])));
    }

    public function getSettingGqlTypes(): array
    {
        $columns = [
            'heading' => Type::string(),
            'handle' => Type::string(),
            'width' => Type::string(),
            'type' => Type::string(),
        ];

        // Figure something out with table defaults. It almost can't be done because we're
        // getting this information from the class, not an instance of the field.

        $typeArray = KeyValueGenerator::generateTypes($this, $columns);

        return array_merge(parent::getSettingGqlTypes(), [
            'columns' => [
                'name' => 'columns',
                'type' => Type::listOf(array_pop($typeArray)),
            ],
        ]);
    }

    public function populateValue(mixed $value, ?Submission $submission): void
    {
        // In case tables have the older format before `col*` indexes
        $columns = [];

        foreach ($this->columns as $key => $col) {
            $columns[$col['handle']] = $key;
        }

        // Allow population via either `col1` or the handle of the column
        if (is_array($value)) {
            foreach ($value as $rowKey => $row) {
                foreach ($row as $colKey => $colValue) {
                    if (!str_starts_with($colKey, 'col')) {
                        $col = $columns[$colKey] ?? null;

                        if ($col) {
                            $value[$rowKey][$col] = $colValue;
                            $value[$rowKey]['col' . $col] = $colValue;
                        }
                    }
                }
            }
        }

        parent::populateValue($value, $submission);
    }

    public function getInitialValue(?ElementInterface $element = null): mixed
    {
        $value = parent::getInitialValue($element);

        if ($value instanceof TableFieldValue) {
            return $value;
        }

        return new TableFieldValue($value, $this->columns);
    }

    public function beforeSave(bool $isNew): bool
    {
        $settings = $this->getSettings();

        $columns = [];

        // The CP builder posts columns as an ordered row array, but persistence keeps them
        // keyed by column ID for stable storage and lookups.
        foreach ($settings['columns'] as $colId => $column) {
            $id = ArrayHelper::remove($column, 'id', $colId);

            $columns[$id] = $column;
        }

        $this->columns = $columns;

        return parent::beforeSave($isNew);
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::tableField([
                '$field' => 'formieTableColumns',
                'label' => Craft::t('formie', 'Table Columns'),
                'instructions' => Craft::t('formie', 'Define the columns your table should have.'),
                'name' => 'columns',
                'newRowDefaults' => [
                    'type' => 'singleline',
                ],
                'columns' => [
                    [
                        'type' => 'text',
                        'name' => 'heading',
                        'label' => Craft::t('formie', 'Column Heading'),
                    ],
                    [
                        'type' => 'handle',
                        'name' => 'handle',
                        'label' => Craft::t('formie', 'Handle'),
                        'variant' => 'code',
                        'source' => 'heading',
                    ],
                    [
                        'type' => 'text',
                        'name' => 'width',
                        'label' => Craft::t('formie', 'Width'),
                        'thin' => true,
                    ],
                    [
                        'type' => 'select',
                        'name' => 'type',
                        'label' => Craft::t('formie', 'Type'),
                        'thin' => true,
                        'options' => [
                            ['label' => Craft::t('formie', 'Checkbox'), 'value' => 'checkbox'],
                            ['label' => Craft::t('formie', 'Color'), 'value' => 'color'],
                            ['label' => Craft::t('formie', 'Date'), 'value' => 'date'],
                            ['label' => Craft::t('formie', 'Dropdown'), 'value' => 'select'],
                            ['label' => Craft::t('formie', 'Email'), 'value' => 'email'],
                            ['label' => Craft::t('formie', 'Heading'), 'value' => 'heading'],
                            ['label' => Craft::t('formie', 'Multi-line Text'), 'value' => 'multiline'],
                            ['label' => Craft::t('formie', 'Number'), 'value' => 'number'],
                            ['label' => Craft::t('formie', 'Time'), 'value' => 'time'],
                            ['label' => Craft::t('formie', 'Single-line Text'), 'value' => 'singleline'],
                            ['label' => Craft::t('formie', 'URL'), 'value' => 'url'],
                        ],
                    ],
                ],
            ]),
            SchemaHelper::tableField([
                '$field' => 'formieTableDefaults',
                'label' => Craft::t('formie', 'Default Values'),
                'instructions' => Craft::t('formie', 'Define the default values for the field.'),
                'name' => 'defaults',
                'columnsSource' => 'columns',
                'columns' => [],
                // 'useColumnIds' => true,
                // 'columns' => 'settings.columns',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Add Row Label'),
                'instructions' => Craft::t('formie', 'The label for the button that adds another row.'),
                'name' => 'addRowLabel',
                'validation' => 'required',
                'required' => true,
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::includeInEmailFieldSummariesField(),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Static'),
                'instructions' => Craft::t('formie', 'Whether this field should disallow adding more rows, showing only the default rows.'),
                'name' => 'static',
            ]),
        ];
    }

    public function defineFormBuilderValidationSchema(): array
    {
        return [
            SchemaHelper::requiredField(),
            SchemaHelper::requiredValidationMessage(),
            SchemaHelper::minRowsField(['if' => 'static != true']),
            SchemaHelper::maxRowsField(['if' => 'static != true']),
        ];
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
            SchemaHelper::errorMessagePosition($this),
        ];
    }

    public function defineFormBuilderAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
        ];
    }

    public function defineFormBuilderConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }


    // Protected Methods
    // =========================================================================

    protected function defineAllowPrimaryReference(): bool
    {
        return false;
    }

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;
        $id = $this->getHtmlId($form);
        $templateId = "{$id}-template";

        if ($key === 'fieldLayout') {
            return SlotTag::make('fieldset')
                ->core([
                    'data-formie-field-layout' => true,
                    'data-formie-table-field-layout' => true,
                    'data-formie-template-id' => $templateId,
                    'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
                ])
                ->theme([
                    'class' => [
                        'formie-field-layout',
                        'formie-table-field-layout',
                    ],
                ]);
        }

        if ($key === 'fieldLabel') {
            $labelPosition = $context->get('labelPosition');

            return SlotTag::make('legend')
                ->core([
                    'data-formie-label' => true,
                    'data-formie-field-label' => true,
                    'data-formie-table-field-label' => true,
                    'data-formie-sr-only' => $labelPosition instanceof HiddenPosition ? true : false,
                ])
                ->theme([
                    'class' => [
                        'formie-label',
                        'formie-field-label',
                        'formie-table-field-label',
                        $labelPosition instanceof HiddenPosition ? 'formie-sr-only' : false,
                    ],
                ]);
        }

        if ($key === 'fieldTableWrapper') {
            return SlotTag::make('div')
                ->core([
                    'data-formie-table-wrapper' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-table-wrapper',
                    ],
                ]);
        }

        if ($key === 'fieldTable') {
            return SlotTag::make('table')
                ->core([
                    'data-formie-table' => true,
                    'data-formie-template-id' => $templateId,
                ])
                ->theme([
                    'class' => [
                        'formie-table',
                    ],
                ]);
        }

        if ($key === 'fieldTableHeader') {
            return SlotTag::make('thead')
                ->core([
                    'data-formie-table-header' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-table-header',
                    ],
                ]);
        }

        if ($key === 'fieldTableHeaderRow') {
            return SlotTag::make('tr')
                ->core([
                    'data-formie-table-header-row' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-table-header-row',
                    ],
                ]);
        }

        if ($key === 'fieldTableHeaderColumn') {
            $col = $context->get('col', []);
            $width = $col['width'] ?? false;
            $type = $col['type'] ?? false;

            return SlotTag::make('th')
                ->core([
                    'data-formie-table-header-column' => true,
                    'data-formie-table-column-handle' => $col['handle'] ?? false,
                    'data-formie-table-column-type' => $type,
                    'width' => $width,
                ])
                ->theme([
                    'class' => [
                        'formie-table-header-column',
                        $type ? "formie-table-header-column-{$type}" : false,
                    ],
                ]);
        }

        if ($key === 'fieldTableBody') {
            return SlotTag::make('tbody')
                ->core([
                    'data-formie-table-body' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-table-body',
                    ],
                ]);
        }

        if ($key === 'fieldTableBodyRow') {
            return SlotTag::make('tr')
                ->core([
                    'data-formie-table-row' => true,
                    'data-formie-table-row-id' => $context->get('index', null),
                ])
                ->theme([
                    'class' => [
                        'formie-table-row',
                    ],
                ]);
        }

        if ($key === 'fieldTableBodyColumn') {
            $col = $context->get('col', []);
            $type = $col['type'] ?? false;

            return SlotTag::make('td')
                ->core([
                    'data-formie-table-body-column' => true,
                    'data-formie-table-column-id' => $context->get('colId', false),
                    'data-formie-table-column-handle' => $col['handle'] ?? false,
                    'data-formie-table-column-type' => $type,
                ])
                ->theme([
                    'class' => [
                        'formie-table-body-column',
                        $type ? "formie-table-body-column-{$type}" : false,
                    ],
                ]);
        }

        if ($key === 'fieldTableRemoveColumn') {
            return SlotTag::make('td')
                ->core([
                    'data-formie-table-remove-column' => true,
                    'data-col-remove' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-table-remove-column',
                    ],
                ]);
        }

        if ($key === 'fieldAddButton') {
            $isStatic = false;

            // Disable the button straight away if we're making it static
            if ($this->minRows && $this->maxRows && $this->minRows == $this->maxRows) {
                $isStatic = true;
            }

            if ($this->static) {
                return null;
            }

            return SlotTag::make('button')
                ->core([
                    'type' => 'button',
                    'text' => Craft::t('formie', $this->addRowLabel),
                    'disabled' => $isStatic,
                    'data-formie-add-button' => true,
                    'data-formie-table-add' => $this->handle,
                    'data-formie-template-id' => $templateId,
                    'data-formie-min-rows' => $this->minRows,
                    'data-formie-max-rows' => $this->maxRows,
                ])
                ->theme([
                    'class' => [
                        'formie-button',
                        'formie-table-add-button',
                    ],
                ]);
        }

        if ($key === 'fieldRemoveButton') {
            return SlotTag::make('button')
                ->core([
                    'type' => 'button',
                    'text' => Craft::t('formie', 'Remove'),
                    'aria-label' => Craft::t('formie', 'Remove row'),
                    'title' => Craft::t('formie', 'Remove row'),
                    'data-formie-remove-button' => true,
                    'data-formie-icon' => 'close',
                    'data-formie-table-remove' => $this->handle,
                ])
                ->theme([
                    'class' => [
                        'formie-button',
                        'formie-button-icon',
                        'formie-table-remove-button',
                    ],
                ]);
        }

        if ($key === 'tableCheckboxInput') {
            return SlotTag::make('input')
                ->core([
                    'type' => 'checkbox',
                    'data-formie-table-input' => true,
                    'data-formie-table-input-type' => 'checkbox',
                ])
                ->theme([
                    'class' => $this->_getTableInputClasses('checkbox'),
                ]);
        }

        if ($key === 'tableColorInput') {
            return SlotTag::make('input')
                ->core([
                    'type' => 'color',
                    'data-formie-table-input' => true,
                    'data-formie-table-input-type' => 'color',
                ])
                ->theme([
                    'class' => $this->_getTableInputClasses('color'),
                ]);
        }

        if ($key === 'tableDateInput') {
            return SlotTag::make('input')
                ->core([
                    'type' => 'date',
                    'data-formie-table-input' => true,
                    'data-formie-table-input-type' => 'date',
                ])
                ->theme([
                    'class' => $this->_getTableInputClasses('date'),
                ]);
        }

        if ($key === 'tableEmailInput') {
            return SlotTag::make('input')
                ->core([
                    'type' => 'email',
                    'data-formie-table-input' => true,
                    'data-formie-table-input-type' => 'email',
                ])
                ->theme([
                    'class' => $this->_getTableInputClasses('email'),
                ]);
        }

        if ($key === 'tableHeadingInput') {
            return SlotTag::make('input')
                ->core([
                    'type' => 'hidden',
                    'data-formie-table-input' => true,
                    'data-formie-table-input-type' => 'heading',
                ])
                ->theme([
                    'class' => $this->_getTableInputClasses('heading'),
                ]);
        }

        if ($key === 'tableMultilineInput') {
            return SlotTag::make('textarea')
                ->core([
                    'data-formie-table-input' => true,
                    'data-formie-table-input-type' => 'multiline',
                ])
                ->theme([
                    'class' => $this->_getTableInputClasses('multiline'),
                ]);
        }

        if ($key === 'tableNumberInput') {
            return SlotTag::make('input')
                ->core([
                    'type' => 'number',
                    'data-formie-table-input' => true,
                    'data-formie-table-input-type' => 'number',
                ])
                ->theme([
                    'class' => $this->_getTableInputClasses('number'),
                ]);
        }

        if ($key === 'tableSelectInput') {
            return SlotTag::make('select')
                ->core([
                    'data-formie-table-input' => true,
                    'data-formie-table-input-type' => 'select',
                ])
                ->theme([
                    'class' => $this->_getTableInputClasses('select'),
                ]);
        }

        if ($key === 'tableSinglelineInput') {
            return SlotTag::make('input')
                ->core([
                    'type' => 'text',
                    'data-formie-table-input' => true,
                    'data-formie-table-input-type' => 'singleline',
                ])
                ->theme([
                    'class' => $this->_getTableInputClasses('singleline'),
                ]);
        }

        if ($key === 'tableTimeInput') {
            return SlotTag::make('input')
                ->core([
                    'type' => 'time',
                    'data-formie-table-input' => true,
                    'data-formie-table-input-type' => 'time',
                ])
                ->theme([
                    'class' => $this->_getTableInputClasses('time'),
                ]);
        }

        if ($key === 'tableUrlInput') {
            return SlotTag::make('input')
                ->core([
                    'type' => 'url',
                    'data-formie-table-input' => true,
                    'data-formie-table-input-type' => 'url',
                ])
                ->theme([
                    'class' => $this->_getTableInputClasses('url'),
                ]);
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['minRows'], 'compare', 'compareAttribute' => 'maxRows', 'operator' => '<=', 'type' => 'number', 'when' => [$this, 'hasMaxRows']];
        $rules[] = [['maxRows'], 'compare', 'compareAttribute' => 'minRows', 'operator' => '>=', 'type' => 'number', 'when' => [$this, 'hasMinRows']];
        $rules[] = [['minRows', 'maxRows'], 'integer', 'min' => 0];
        $rules[] = [['columns'], 'validateColumns'];

        return $rules;
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        Craft::$app->getView()->registerAssetBundle(TimepickerAsset::class);

        if (empty($this->columns)) {
            return '';
        }

        // Translate the column headings
        foreach ($this->columns as &$column) {
            if (!empty($column['heading'])) {
                $column['heading'] = Craft::t('formie', $column['heading']);
            }

            if (!empty($column['options'])) {
                array_walk($column['options'], function(&$option) {
                    $option['label'] = Craft::t('formie', $option['label']);
                });
            }
        }

        unset($column);

        if (!is_array($value)) {
            $value = [];
        }

        // Explicitly set each cell value to an array with a 'value' key
        $checkForErrors = $element && $element->hasErrors($this->handle);

        foreach ($value as &$row) {
            foreach ($this->columns as $colId => $col) {
                if (isset($row[$colId])) {
                    $hasErrors = $checkForErrors && !$this->_validateCellValue($col['type'], $row[$colId]);
                    $row[$colId] = [
                        'value' => $row[$colId],
                        'hasErrors' => $hasErrors,
                    ];
                }
            }
        }

        unset($row);

        // Make sure the value contains at least the minimum number of rows
        if ($this->minRows) {
            for ($i = count($value); $i < $this->minRows; $i++) {
                $value[] = [];
            }
        }

        return Craft::$app->getView()->renderTemplate('_includes/forms/editableTable', [
            'id' => Html::id($this->handle),
            'name' => $this->handle,
            'cols' => $this->columns,
            'rows' => $value,
            'minRows' => $this->minRows,
            'maxRows' => $this->maxRows,
            'static' => false,
            'staticRows' => $this->staticRows,
            'allowAdd' => true,
            'allowDelete' => true,
            'allowReorder' => true,
            'addRowLabel' => Craft::t('formie', $this->addRowLabel),
        ]);
    }

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        $values = [];

        if (!is_array($value)) {
            $value = [];
        }

        foreach ($value as $rowId => $row) {
            foreach ($this->columns as $colId => $col) {
                // Ensure column values are prepped correctly
                $cellValue = $row[$col['handle']] ?? null;
                $cellValue = $this->_normalizeCellValueAsString($col['type'], $cellValue);

                $values[] = $cellValue;
            }
        }

        return implode(', ', $values);
    }

    protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed
    {
        $values = [];

        if (!is_array($value)) {
            $value = [];
        }

        foreach ($value as $rowId => $row) {
            foreach ($this->columns as $colId => $col) {
                // Ensure column values are prepped correctly
                $cellValue = $row[$col['handle']] ?? null;
                $cellValue = $this->_normalizeCellValueAsString($col['type'], $cellValue);

                $values[$this->getExportLabel($element) . ': ' . ($rowId + 1) . ': ' . $col['heading']] = $cellValue;
            }
        }

        return $values;
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): mixed
    {
        $headValues = '';
        $bodyValues = '';

        if (!is_array($value)) {
            $value = [];
        }

        foreach ($value as $rowId => $row) {
            $rowValues = '';

            foreach ($this->columns as $colId => $col) {
                // Ensure column values are prepped correctly
                $cellValue = $row[$col['handle']] ?? null;
                $cellValue = $this->_normalizeCellValueAsString($col['type'], $cellValue);

                $rowValues .= Html::tag('td', $cellValue);
            }

            $bodyValues .= Html::tag('tr', $rowValues);
        }

        $tbody = Html::tag('tbody', $bodyValues);

        foreach ($this->columns as $colId => $col) {
            $headValues .= Html::tag('th', $col['heading']);
        }

        $thead = Html::tag('thead', Html::tag('tr', $headValues));

        return Template::raw(Html::tag('table', $thead . $tbody));
    }

    protected function defineClientInput(): array
    {
        return array_merge(parent::defineClientInput(), [
            'staticRows' => $this->staticRows,
            'static' => $this->static,
            'addRowLabel' => $this->addRowLabel,
            'minRows' => $this->minRows,
            'maxRows' => $this->maxRows,
            'columns' => array_values(array_map(static function(string $id, array $column) {
                return array_merge(['id' => $id], $column);
            }, array_keys($this->columns), $this->columns)),
        ]);
    }

    protected function defineClientModules(): array
    {
        $modules = parent::defineClientModules();
        
        $modules[] = new ClientModule([
            'id' => 'table',
            'renderTargets' => [ClientModule::RENDER_TARGET_FRONTEND],
            'config' => [
                'static' => $this->static,
            ],
        ]);

        return $modules;
    }

    protected function defineValueClass(): ?string
    {
        return TableFieldValue::class;
    }


    // Private Methods
    // =========================================================================

    private function _normalizeValueInternal(mixed $value, ?ElementInterface $element, bool $fromRequest): ?array
    {
        if (empty($this->columns)) {
            return null;
        }

        $defaults = $this->defaults ?? [];

        // Apply static translations
        foreach ($defaults as &$row) {
            foreach ($this->columns as $colId => $col) {
                if ($col['type'] === 'heading' && isset($row[$colId])) {
                    $row[$colId] = Craft::t('formie', $row[$colId]);
                }
            }
        }

        if (is_string($value) && !empty($value)) {
            $value = Json::decodeIfJson($value);
        } else if ($value === null) {
            $value = $defaults;
        }

        if (!is_array($value)) {
            $value = [];
        }

        // Keep each cell addressable by both the internal column ID and the
        // authored handle. Builder/editor paths prefer handles, while persisted
        // data and schema definitions still key by column ID.
        $value = array_values($value);

        if ($this->staticRows) {
            $valueRows = count($value);
            $totalRows = count($defaults);

            if ($valueRows < $totalRows) {
                $value = array_pad($value, $totalRows, []);
            } else if ($valueRows > $totalRows) {
                array_splice($value, $totalRows);
            }
        }

        // If the value is still empty, return null
        if (empty($value)) {
            return null;
        }

        foreach ($value as $rowIndex => &$row) {
            foreach ($this->columns as $colId => $col) {
                if ($col['type'] === 'heading') {
                    $cellValue = $defaults[$rowIndex][$colId] ?? '';
                } else if (array_key_exists($colId, $row)) {
                    $cellValue = $row[$colId];
                } else if ($col['handle'] && array_key_exists($col['handle'], $row)) {
                    $cellValue = $row[$col['handle']];
                } else {
                    $cellValue = null;
                }

                $cellValue = $this->_normalizeCellValue($col['type'], $cellValue, $fromRequest);
                $row[$colId] = $cellValue;

                if ($col['handle']) {
                    $row[$col['handle']] = $cellValue;
                }
            }
        }

        // The editable table UI carries a placeholder/template row keyed by `__ROW__`.
        // If that leaks into the posted payload, drop it before normalizing persisted rows.
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if ($k === '__ROW__') {
                    unset($value[$k]);
                }
            }
        }

        return $value;
    }

    private function _validateCellValue(string $type, mixed $value, string &$error = null): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        switch ($type) {
            case 'color':
                if ($value instanceof ColorFieldValue) {
                    $value = $value->getHex();
                }

                $validator = new ColorValidator();
                break;
            case 'url':
                $validator = new UrlValidator();
                break;
            case 'email':
                $validator = new EmailValidator();
                break;
            default:
                return true;
        }

        $validator->message = str_replace('{attribute}', '{value}', $validator->message);

        return $validator->validate($value, $error);
    }

    private function _normalizeCellValueAsString(string $type, mixed $value): mixed
    {
        return match ($type) {
            'color' => $value instanceof ColorFieldValue ? $value->getHex() : ($value ?? ''),
            'date', 'time' => null,
            default => $value,
        };
    }

    private function _serializeCellValue(string $type, mixed $value): mixed
    {
        if ($type === 'color' && $value instanceof ColorFieldValue) {
            return $value->getHex();
        }

        return $value;
    }

    private function _normalizeCellValue(string $type, mixed $value, bool $fromRequest): mixed
    {
        switch ($type) {
            case 'color':
                if ($value instanceof ColorFieldValue) {
                    return $value;
                }

                if (!$value || $value === '#') {
                    return null;
                }

                $value = strtolower($value);

                if ($value[0] !== '#') {
                    $value = '#' . $value;
                }

                if (strlen($value) === 4) {
                    $value = '#' . $value[1] . $value[1] . $value[2] . $value[2] . $value[3] . $value[3];
                }

                return new ColorFieldValue($value);

            case 'multiline':
            case 'singleline':
                if ($value !== null) {
                    if (!$fromRequest) {
                        $value = StringHelper::unescapeShortcodes(StringHelper::shortcodesToEmoji($value));
                    }

                    return trim(preg_replace('/\R/u', "\n", $value));
                }
                // no break
            case 'date':
            case 'time':
                return DateTimeHelper::toDateTime($value, false, false) ?: null;
        }

        return $value;
    }

    private function _getTableInputClasses(string $type): array
    {
        $classes = [
            'formie-table-input',
            "formie-table-{$type}-input",
        ];

        if ($type === 'checkbox') {
            $classes[] = 'formie-input';
            $classes[] = 'formie-checkbox-input';
        }

        if (in_array($type, ['singleline', 'email', 'number', 'color', 'date', 'time', 'url'], true)) {
            $classes[] = 'formie-input';
        }

        if ($type === 'multiline') {
            $classes[] = 'formie-textarea';
        }

        if ($type === 'select') {
            $classes[] = 'formie-select';
        }

        return $classes;
    }
}
