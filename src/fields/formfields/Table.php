<?php
namespace verbb\formie\fields\formfields;

use Craft;
use craft\base\Element;
use craft\fields\data\ColorData;
use craft\fields\Table as CraftTable;
use craft\base\ElementInterface;
use craft\helpers\ArrayHelper;
use craft\validators\ArrayValidator;
use craft\validators\ColorValidator;
use craft\validators\UrlValidator;

use verbb\formie\helpers\SchemaHelper;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\FormFieldTrait;

use yii\db\Schema;
use yii\validators\EmailValidator;

class Table extends CraftTable implements FormFieldInterface
{
    // Traits
    // =========================================================================

    use FormFieldTrait;


    // Properties
    // =========================================================================

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
                'skipOnEmpty' => false,
                'on' => Element::SCENARIO_LIVE,
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
     * @inheritDoc
     */
    public function getSavedSettings(): array
    {
        $settings = $this->getSettings();

        // Translate the columns options into an array of objects, rather than just a collection of objects
        // Vue can't really deal with that, but let's keep it the same as Craft's Table field
        foreach ($settings['columns'] as $key => &$column) {
            $column['id'] = $key;
        }

        $settings['columns'] = array_values($settings['columns']);

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
        foreach ($settings['columns'] as $column) {
            $id = ArrayHelper::remove($column, 'id');

            $columns[$id] = $column;
        }

        $this->columns = $columns;

        return parent::beforeSave($isNew);
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


    // Private Methods
    // =========================================================================

    /**
     * Validates a cell’s value.
     *
     * @param string $type The cell type
     * @param mixed $value The cell value
     * @param string|null &$error The error text to set on the element
     * @return bool Whether the value is valid
     * @see normalizeValue()
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
}
