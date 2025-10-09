<?php
namespace verbb\formie\fields;

use verbb\formie\base\FieldInterface;
use verbb\formie\base\Field;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\fields\data\ColorData;
use verbb\formie\gql\types\TableRowType;
use verbb\formie\gql\types\generators\KeyValueGenerator;
use verbb\formie\gql\types\generators\TableRowTypeGenerator;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;
use verbb\formie\positions\Hidden as HiddenPosition;

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
        // TODO: fixes an issue with dropdown options and FormKit nested form.
        // Can be removed once we implement proper FormKit repeater.
        if (array_key_exists('tableDropdownOptions', $config)) {
            unset($config['tableDropdownOptions']);
        }

        // Setup defaults for some values which can't be set in the property definition
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

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/table/preview', [
            'field' => $this,
        ]);
    }

    public function getFrontEndJsModules(): ?array
    {
        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/fields/table.js'),
            'module' => 'FormieTable',
            'settings' => [
                'static' => $this->static,
            ],
        ];
    }

    public function getFormBuilderSettings(): array
    {
        $settings = parent::getFormBuilderSettings();

        // Translate the columns options into an array of objects, rather than just a collection of objects
        // Vue can't really deal with that, but let's keep it the same as Craft's Table field

        // But - DON'T do this if there are field errors! We want to keep it in a Vue-format to continue editing before a save.
        if (!$this->hasErrors()) {
            foreach ($settings['columns'] as $key => &$column) {
                $column['id'] = $key;
            }

            unset($column);

            $settings['columns'] = array_values($settings['columns']);
        } else {
            // If there are errors though, Craft's table field validation may very likely return an array
            // for some attributes. We don't want that, so remove them back to single values.
            foreach ($settings['columns'] as $colId => $column) {
                foreach ($column as $key => $col) {
                    if (is_array($col)) {
                        $settings['columns'][$colId][$key] = $col['value'] ?? '';
                    }
                }
            }
        }

        // Normalize defaults, which might cause reactivity issues
        // https://github.com/verbb/formie/issues/2584
        if (isset($settings['defaults']) && is_array($settings['defaults'])) {
            foreach ($settings['defaults'] as $key => $row) {
                unset($settings['defaults'][$key]['id']);

                // If the default row is empty, ensure we cast it as an object for proper
                // reactivity with Vue. Otherwise empty rows are cast to arrays.
                // i.e. { "defaults": [[], [], []] } becomes { "defaults": [{}, {}, {}] }
                if (!$row) {
                    $settings['defaults'][$key] = new \stdClass();
                }
            }
        }

        return $settings;
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();
        $rules[] = ['validateTableData'];

        return $rules;
    }

    public function validateTableData(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->fieldKey);

        if (!empty($value) && !empty($this->columns)) {
            foreach ($value as &$row) {
                foreach ($this->columns as $colId => $col) {
                    if (is_string($row[$colId])) {
                        // Trim the value before validating
                        $row[$colId] = trim($row[$colId]);
                    }

                    if (!$this->_validateCellValue($col['type'], $row[$colId], $error)) {
                        $element->addError($this->fieldKey, $error);
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

                $value = $row[$colId];

                if (is_string($value) && !$supportsMb4) {
                    $value = StringHelper::emojiToShortcodes(StringHelper::escapeShortcodes($value));
                }

                $serializedRow[$colId] = parent::serializeValue($value ?? null, null);
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

    public function beforeSave(bool $isNew): bool
    {
        $settings = $this->getSettings();

        $columns = [];

        // We've got a regular array from Vue, but we need to translate that back to an object.
        foreach ($settings['columns'] as $colId => $column) {
            $id = ArrayHelper::remove($column, 'id', $colId);

            $columns[$id] = $column;
        }

        $this->columns = $columns;

        return parent::beforeSave($isNew);
    }

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Table Columns'),
                'help' => Craft::t('formie', 'Define the columns your table should have.'),
                'name' => 'columns',
                'generateHandle' => 'heading:handle',
                'useColumnIds' => true,
                'newRowDefaults' => [
                    'heading' => '',
                    'handle' => '',
                    'width' => '',
                    'type' => 'singleline',
                ],
                'columns' => [
                    [
                        'type' => 'label',
                        'name' => 'heading',
                        'label' => Craft::t('formie', 'Column Heading'),
                        'class' => 'singleline-cell textual',
                    ],
                    [
                        'type' => 'value',
                        'name' => 'handle',
                        'label' => Craft::t('formie', 'Handle'),
                        'class' => 'code singleline-cell textual',
                    ],
                    [
                        'type' => 'width',
                        'label' => Craft::t('formie', 'Width'),
                        'class' => 'code singleline-cell textual',
                        'width' => 50,
                    ],
                    [
                        'type' => 'type',
                        'label' => Craft::t('formie', 'Type'),
                        'class' => 'thin select-cell',
                    ],
                ],
            ]),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Default Values'),
                'help' => Craft::t('formie', 'Define the default values for the field.'),
                'name' => 'defaults',
                'validation' => '',
                'useColumnIds' => true,
                'columns' => 'settings.columns',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Add Row Label'),
                'help' => Craft::t('formie', 'The label for the button that adds another row.'),
                'name' => 'addRowLabel',
                'validation' => 'required',
                'required' => true,
            ]),
        ];
    }

    public function defineSettingsSchema(): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Required Field'),
                'help' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
                'name' => 'required',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Error Message'),
                'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                'name' => 'errorMessage',
                'if' => '$get(required).value',
            ]),
            SchemaHelper::includeInEmailField(),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Static'),
                'help' => Craft::t('formie', 'Whether this field should disallow adding more rows, showing only the default rows.'),
                'name' => 'static',
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Minimum instances'),
                'help' => Craft::t('formie', 'The minimum required number of rows in this table that must be completed.'),
                'name' => 'minRows',
                'if' => '$get(static).value != true',
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Maximum instances'),
                'help' => Craft::t('formie', 'The maximum required number of rows in this table that must be completed.'),
                'name' => 'maxRows',
                'if' => '$get(static).value != true',
            ]),
        ];
    }

    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
        ];
    }

    public function defineAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
        ];
    }

    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        $form = $context['form'] ?? null;

        $id = $this->getHtmlId($form);

        if ($key === 'fieldContainer') {
            return new HtmlTag('fieldset', [
                'class' => 'fui-fieldset',
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ]);
        }

        if ($key === 'fieldLabel') {
            $labelPosition = $context['labelPosition'] ?? null;

            return new HtmlTag('legend', [
                'class' => [
                    'fui-legend',
                ],
                'data' => [
                    'field-label' => true,
                    'fui-sr-only' => $labelPosition instanceof HiddenPosition ? true : false,
                ],
            ]);
        }

        if ($key === 'fieldTable') {
            return new HtmlTag('table', [
                'class' => 'fui-table',
            ]);
        }

        if ($key === 'fieldTableHeader') {
            return new HtmlTag('thead');
        }

        if ($key === 'fieldTableHeaderRow') {
            return new HtmlTag('tr');
        }

        if ($key === 'fieldTableHeaderColumn') {
            $col = $context['col'] ?? [];
            $width = $col['width'] ?? false;

            return new HtmlTag('th', [
                'data-handle' => $col['handle'],
                'data-type' => $col['type'],
                'width' => $width,
            ]);
        }

        if ($key === 'fieldTableBody') {
            return new HtmlTag('tbody', [
                'class' => 'fui-table-rows',
            ]);
        }

        if ($key === 'fieldTableBodyRow') {
            return new HtmlTag('tr', [
                'class' => 'fui-table-row',
                'data-table-row' => true,
            ]);
        }

        if ($key === 'fieldTableBodyColumn') {
            return new HtmlTag('td', [
                'data-col' => $context['colId'] ?? false,
                'data-col-handle' => $context['col']['handle'] ?? false,
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

            return new HtmlTag('button', [
                'class' => [
                    'fui-btn fui-table-add-btn',
                    $isStatic ? 'fui-disabled' : false,
                ],
                'type' => 'button',
                'text' => Craft::t('formie', $this->addRowLabel),
                'disabled' => $isStatic,
                'data' => [
                    'min-rows' => $this->minRows,
                    'max-rows' => $this->maxRows,
                    'add-table-row' => $this->handle,
                ],
            ]);
        }

        if ($key === 'fieldRemoveButton') {
            return new HtmlTag('button', [
                'class' => 'fui-btn fui-table-remove-btn',
                'type' => 'button',
                'text' => Craft::t('formie', 'Remove'),
                'data' => [
                    'remove-table-row' => $this->handle,
                ],
            ]);
        }

        if ($key === 'tableCheckboxInput') {
            return new HtmlTag('input', [
                'type' => 'checkbox',
                'class' => 'fui-input fui-checkbox-input',
            ]);
        }

        if ($key === 'tableColorInput') {
            return new HtmlTag('input', [
                'type' => 'color',
                'class' => 'fui-input',
            ]);
        }

        if ($key === 'tableDateInput') {
            return new HtmlTag('input', [
                'type' => 'date',
                'class' => 'fui-input',
            ]);
        }

        if ($key === 'tableEmailInput') {
            return new HtmlTag('input', [
                'type' => 'email',
                'class' => 'fui-input',
            ]);
        }

        if ($key === 'tableHeadingInput') {
            return new HtmlTag('input', [
                'type' => 'hidden',
            ]);
        }

        if ($key === 'tableMultilineInput') {
            return new HtmlTag('textarea', [
                'class' => 'fui-input',
            ]);
        }

        if ($key === 'tableNumberInput') {
            return new HtmlTag('input', [
                'type' => 'number',
                'class' => 'fui-input',
            ]);
        }

        if ($key === 'tableSelectInput') {
            return new HtmlTag('select', [
                'class' => 'fui-select',
            ]);
        }

        if ($key === 'tableSinglelineInput') {
            return new HtmlTag('input', [
                'type' => 'text',
                'class' => 'fui-input',
            ]);
        }

        if ($key === 'tableTimeInput') {
            return new HtmlTag('input', [
                'type' => 'time',
                'class' => 'fui-input',
            ]);
        }

        if ($key === 'tableUrlInput') {
            return new HtmlTag('input', [
                'type' => 'url',
                'class' => 'fui-input',
            ]);
        }

        return parent::defineHtmlTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['minRows'], 'compare', 'compareAttribute' => 'maxRows', 'operator' => '<=', 'type' => 'number', 'when' => [$this, 'hasMaxRows']];
        $rules[] = [['maxRows'], 'compare', 'compareAttribute' => 'minRows', 'operator' => '>=', 'type' => 'number', 'when' => [$this, 'hasMinRows']];
        $rules[] = [['minRows', 'maxRows'], 'integer', 'min' => 0];
        $rules[] = [['columns'], 'validateColumns'];

        return $rules;
    }

    protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
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
            'id' => $this->getInputId(),
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

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): string
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

        $this->defaultValue = $value;
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

        // Normalize the values and make them accessible from both the col IDs and the handles
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

        // Because we have to have our row template as HTML due to Vue3 support (not in a `script` tag)
        // it unfortunately gets submitted as content for the field. We need to filter out - its invalid.
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
                /** @var ColorData $value */
                $value = $value->getHex();
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
            'color' => $value->getHex(),
            'date', 'time' => null,
            default => $value,
        };
    }

    private function _normalizeCellValue(string $type, mixed $value, bool $fromRequest): mixed
    {
        switch ($type) {
            case 'color':
                if ($value instanceof ColorData) {
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

                return new ColorData($value);

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
}
