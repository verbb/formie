<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\FormFieldTrait;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\gql\types\generators\KeyValueGenerator;
use verbb\formie\models\HtmlTag;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\fields\data\ColorData;
use craft\fields\Table as CraftTable;
use craft\helpers\ArrayHelper;
use craft\helpers\Component;
use craft\helpers\DateTimeHelper;
use craft\helpers\Html;
use craft\helpers\Template;
use craft\validators\ArrayValidator;
use craft\validators\ColorValidator;
use craft\validators\UrlValidator;

use GraphQL\Type\Definition\Type;

use yii\db\Schema;
use yii\validators\EmailValidator;

class Table extends CraftTable implements FormFieldInterface
{
    // Traits
    // =========================================================================

    use FormFieldTrait {
        getSettingGqlTypes as traitGetSettingGqlTypes;
        defineHtmlTag as traitDefineHtmlTag;
    }


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Table');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/table/icon.svg';
    }


    // Properties
    // =========================================================================

    /**
     * @var bool
     */
    public bool $searchable = true;

    /**
     * Override the default columns from Craft's table field. We don't want default
     * columns, and we don't want an object syntax of `col1`, which is tricky in our current
     * Vue setup. Maybe one day...
     *
     * @var array
     */
    public array $columns = [];

    /**
     * @var bool
     */
    public bool $static = false;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // TODO: fixes an issue with dropdown options and FormKit nested form.
        // Can be removed once we implement proper FormKit repeater.
        if (array_key_exists('tableDropdownOptions', $config)) {
            unset($config['tableDropdownOptions']);
        }

        // Pick up any config upstream (can be removed with the above)
        self::normalizeConfig($config);

        parent::__construct($config);
    }

    /**
     * @inheritDoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_TEXT;
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        return [
            'addRowLabel' => Craft::t('formie', 'Add row'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        if (!$this->static) {
            $rules[] = [
                ArrayValidator::class,
                'min' => $this->minRows ?: null,
                'max' => $this->maxRows ?: null,
                'tooFew' => Craft::t('formie', '{attribute} should contain at least {min, number} {min, plural, one{row} other{rows}}.'),
                'tooMany' => Craft::t('formie', '{attribute} should contain at most {max, number} {max, plural, one{row} other{rows}}.'),
                'message' => Craft::t('formie', '{attribute} must have one item.'),
                'skipOnEmpty' => !$this->minRows,
            ];
        }

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        /** @var Element $element */
        if (empty($this->columns)) {
            return '';
        }

        // Translate the column headings
        foreach ($this->columns as &$column) {
            if (!empty($column['heading'])) {
                $column['heading'] = Craft::t('formie', $column['heading']);
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

        $view = Craft::$app->getView();
        $id = Html::id($this->handle);

        return $view->renderTemplate('formie/_formfields/table/input', [
            'id' => $id,
            'name' => $this->handle,
            'cols' => $this->columns,
            'rows' => $value ?: [''],
            'minRows' => $this->minRows,
            'maxRows' => $this->maxRows,
            'static' => $this->static,
            'addRowLabel' => Craft::t('formie', $this->addRowLabel),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/table/preview', [
            'field' => $this,
        ]);
    }

    public function getFrontEndJsModules(): ?array
    {
        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/table.js', true),
            'module' => 'FormieTable',
            'settings' => [
                'static' => $this->static,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getSavedSettings(): array
    {
        $settings = $this->getSettings();

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

        return $settings;
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        $value = parent::normalizeValue($value, $element);

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

    /**
     * @return bool whether minRows was set
     */
    public function hasMinRows(): bool
    {
        return (bool)$this->minRows;
    }

    /**
     * @return bool whether maxRows was set
     */
    public function hasMaxRows(): bool
    {
        return (bool)$this->maxRows;
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

        return array_merge($this->traitGetSettingGqlTypes(), [
            'columns' => [
                'name' => 'columns',
                'type' => Type::listOf(array_pop($typeArray)),
            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Table Columns'),
                'help' => Craft::t('formie', 'Define the columns your table should have.'),
                'name' => 'columns',
                'validation' => 'min:1,length|uniqueValues|requiredValues',
                'generateHandle' => true,
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

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
        ];
    }

    /**
     * @inheritDoc
     */
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
            return new HtmlTag('legend', [
                'class' => 'fui-legend',
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

        return $this->traitDefineHtmlTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['minRows', 'maxRows'], 'integer', 'min' => 0];
        return $rules;
    }

    protected function defineValueAsString($value, ElementInterface $element = null): string
    {
        $values = [];

        if (!is_array($value)) {
            $value = [];
        }

        foreach ($value as $rowId => $row) {
            foreach ($this->columns as $colId => $col) {
                // Ensure column values are prepped correctly
                $cellValue = $row[$col['handle']] ?? null;
                $cellValue = $this->_normalizeCellValue($col['type'], $cellValue);

                $values[] = $cellValue;
            }
        }

        return implode(', ', $values);
    }

    protected function defineValueForExport($value, ElementInterface $element = null): mixed
    {
        $values = [];

        if (!is_array($value)) {
            $value = [];
        }

        foreach ($value as $rowId => $row) {
            foreach ($this->columns as $colId => $col) {
                // Ensure column values are prepped correctly
                $cellValue = $row[$col['handle']] ?? null;
                $cellValue = $this->_normalizeCellValue($col['type'], $cellValue);

                $values[$this->getExportLabel($element) . ': ' . ($rowId + 1) . ': ' . $col['heading']] = $cellValue;
            }
        }

        return $values;
    }

    protected function defineValueForSummary($value, ElementInterface $element = null): string
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
                $cellValue = $this->_normalizeCellValue($col['type'], $cellValue);

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


    // Private Methods
    // =========================================================================

    /**
     * Validates a cell’s value.
     *
     * @param string $type The cell type
     * @param mixed $value The cell value
     * @param string|null &$error The error text to set on the element
     * @return bool Whether the value is valid
     */
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

    /**
     * Normalizes a cell’s value.
     *
     * @param string $type The cell type
     * @param mixed $value The cell value
     * @return mixed
     */
    private function _normalizeCellValue(string $type, mixed $value): mixed
    {
        return match ($type) {
            'color' => $value->getHex(),
            'date', 'time' => DateTimeHelper::toIso8601($value) ?: null,
            default => $value,
        };
    }
}
