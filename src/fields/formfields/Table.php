<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\FormFieldTrait;
use verbb\formie\elements\Form;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\fields\data\ColorData;
use craft\fields\Table as CraftTable;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\validators\ArrayValidator;
use craft\validators\ColorValidator;
use craft\validators\UrlValidator;

use yii\db\Schema;
use yii\validators\EmailValidator;

class Table extends CraftTable implements FormFieldInterface
{
    // Traits
    // =========================================================================

    use FormFieldTrait;


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
    public $searchable = true;

    /**
     * Override the default columns from Craft's table field. We don't want default
     * columns, and we don't want an object syntax of `col1`, which is tricky in our current
     * Vue setup. Maybe one day...
     *
     * @var array
     */
    public $columns = [];

    /**
     * @var bool
     */
    public $static = false;


    // Public Methods
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
     * @inheritDoc
     */
    public function getIsFieldset(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function renderLabel(): bool
    {
        return !$this->getIsFieldset();
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
                'skipOnEmpty' => !($this->minRows || $this->maxRows),
            ];
        }

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        /** @var Element $element */
        if (empty($this->columns)) {
            return '';
        }

        if (!is_array($this->columns)) {
            $this->columns = [];
        }

        // Translate the column headings
        foreach ($this->columns as &$column) {
            if (!empty($column['heading'])) {
                $column['heading'] = Craft::t('site', $column['heading']);
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
        $id = $view->formatInputId($this->handle);

        return $view->renderTemplate('formie/_formfields/table/input', [
            'id' => $id,
            'name' => $this->handle,
            'cols' => $this->columns,
            'rows' => $value,
            'minRows' => $this->minRows,
            'maxRows' => $this->maxRows,
            'static' => $this->static,
            'addRowLabel' => Craft::t('site', $this->addRowLabel),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/table/preview', [
            'field' => $this
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getFrontEndJsModules()
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

            $settings['columns'] = array_values($settings['columns']);
        } else {
            // If there are errors though, Craft's table field validation may very likely return an array 
            // for some attributes. We don't want that, so remove them back to single values.
            foreach ($settings['columns'] as $colId => &$column) {
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
    public function normalizeValue($value, ElementInterface $element = null)
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
            SchemaHelper::toggleContainer('settings.required', [
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Error Message'),
                    'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                    'name' => 'errorMessage',
                ]),
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Static'),
                'help' => Craft::t('formie', 'Whether this field should disallow adding more rows, showing only the default rows.'),
                'name' => 'static',
            ]),
            SchemaHelper::toggleContainer('!settings.static', [
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Minimum instances'),
                    'help' => Craft::t('formie', 'The minimum required number of rows in this table that must be completed.'),
                    'type' => 'number',
                    'name' => 'minRows',
                    'validation' => 'optional|number|min:0',
                ]),
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Maximum instances'),
                    'help' => Craft::t('formie', 'The maximum required number of rows in this table that must be completed.'),
                    'type' => 'number',
                    'name' => 'maxRows',
                    'validation' => 'optional|number|min:0',
                ]),
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

    /**
     * @inheritDoc
     */
    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function defineValueAsString($value, ElementInterface $element = null)
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

    /**
     * @inheritDoc
     */
    protected function defineValueForExport($value, ElementInterface $element = null)
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

                $values[$this->handle . '_row' . ($rowId + 1) . '_' . $col['handle']] = $cellValue;
            }
        }

        return $values;
    }

    /**
     * @inheritDoc
     */
    protected function defineValueForSummary($value, ElementInterface $element = null)
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
    private function _validateCellValue(string $type, $value, string &$error = null): bool
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
    private function _normalizeCellValue(string $type, $value)
    {
        switch ($type) {
            case 'color':
                return $value->getHex();
            case 'date':
            case 'time':
                return DateTimeHelper::toIso8601($value) ?: null;
        }

        return $value;
    }
}
