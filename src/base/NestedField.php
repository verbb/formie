<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\models\FormRow;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\db\Query;
use craft\db\QueryParam;
use craft\db\Table;
use craft\elements\ElementCollection;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\services\Elements;

use yii\db\ExpressionInterface;

abstract class NestedField extends FormField implements NestedFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function queryCondition(array $instances, mixed $value, array &$params): ?array
    {
        $param = QueryParam::parse($value);

        if (empty($param->values)) {
            return null;
        }

        if ($param->operator === QueryParam::NOT) {
            $param->operator = QueryParam::OR;
            $negate = true;
        } else {
            $negate = false;
        }

        $condition = [$param->operator];
        $qb = Craft::$app->getDb()->getQueryBuilder();
        $valueSql = static::valueSql($instances);

        foreach ($param->values as $key => $value) {
            // Key will likely contain a numeric for the block `0.email` - remove that.
            $key = preg_replace('/^[0-9].*?\./', '', $key);

            $condition[] = $qb->jsonContains($valueSql, [$key => $value]);
        }

        return $negate ? ['not', $condition] : $condition;
    }


    // Properties
    // =========================================================================

    public array $rowsConfig = [];

    private array $_rows = [];


    // Public Methods
    // =========================================================================

    public function hasNestedFields(): bool
    {
        return true;
    }

    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'rowsConfig';

        return $attributes;
    }

    public function getFormBuilderSettings(): array
    {
        $config = parent::getFormBuilderSettings();
        unset($config['rowsConfig']);

        // Return the form builder config for each row
        $config['rows'] = array_map(function($row) {
            return $row->getFormBuilderConfig();
        }, $this->getRows());

        return $config;
    }

    public function getRows(): array
    {
        if ($this->_rows) {
            return $this->_rows;
        }

        // Convert the saved row data into proper models
        $this->_rows = array_map(function($row) {
            return (!($row instanceof FormRow)) ? new FormRow($row) : $row;
        }, $this->rowsConfig);

        // Ensure that each row's field has the correct parent field set on it
        foreach ($this->_rows as $row) {
            foreach ($row->getFields() as $field) {
                $field->setParentField($this);
            }
        }

        return $this->_rows;
    }

    public function setRows(array $rows): void
    {
        // Setting the `rows` attribute from the form builder should populate the raw config data
        $this->rowsConfig = $rows;
    }

    public function validateRows(): void
    {
        foreach ($this->getRows() as $row) {
            if (!$row->validate()) {
                $this->addError('rows', $row->getErrors());
            }
        }
    }

    public function getFields(): array
    {
        $fields = [];

        foreach ($this->getRows() as $row) {
            foreach ($row->getFields() as $field) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    public function afterSave(bool $isNew): void
    {
        // Save all the inner fields
        $fieldsService = Craft::$app->getFields();
        $context = 'formieField:' . $this->uid;

        foreach ($this->getFields() as $field) {
            $field->context = $context;

            $fieldsService->saveField($field);
        }

        $allFieldIds = ArrayHelper::getColumn($this->getFields(), 'id');

        // Handle any deleted fields (that don't exist in the field config anymore)
        foreach ($fieldsService->getAllFields($context) as $field) {
            if (!in_array($field->id, $allFieldIds)) {
                $fieldsService->deleteField($field);
            }
        }

        $rowsConfig = [];

        // Update the serialized data
        foreach ($this->getRows() as $rowKey => $row) {
            foreach ($row->getFields() as $fieldKey => $field) {
                $rowsConfig[$rowKey]['fields'][$fieldKey] = [
                    'fieldUid' => $field->uid,
                    'required' => (bool)$field->required,
                ];
            }
        }

        $this->rowsConfig = $rowsConfig;

        // Have to do a direct query to update the field, rather than `saveField` which will be an infinite loop.
        Db::update(Table::FIELDS, [
            'settings' => Json::encode($this->getSettings()),
        ], ['id' => $this->id]);

        parent::afterSave($isNew);
    }

    public function beforeApplyDelete(): void
    {
        $fieldsService = Craft::$app->getFields();
        $context = 'formieField:' . $this->uid;

        // Delete all inner fields associated with this one
        foreach ($fieldsService->getAllFields($context) as $field) {
            $fieldsService->deleteField($field);
        }

        parent::beforeApplyDelete();
    }

    public function getFrontEndJsModules(): ?array
    {
        $modules = [];

        // Check for any nested fields
        foreach ($this->getFields() as $field) {
            if ($js = $field->getFrontEndJsModules()) {
                // Normalise for processing. Fields can have multiple modules
                if (!isset($js[0])) {
                    $js = [$js];
                }
                
                $modules[] = $js;
            }
        }

        return array_merge(...$modules);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['rows'], 'validateRows'];

        return $rules;
    }

    protected function normalizeFieldValidator(string $attribute, mixed $rule, FormFieldInterface $field, ElementInterface $element, bool $isEmpty): void
    {
        if (is_string($rule)) {
            $rule = [$attribute, $rule];
        }

        if (isset($rule[1])) {
            // Make sure the attribute name starts with 'field:'
            if ($rule[0] === $field->handle) {
                $rule[0] = $attribute;
            }
        } else {
            // ["Validator"] syntax
            array_unshift($rule, $attribute);
        }

        $method = $rule[1] ?? null;

        if (!$isEmpty && $field->hasMethod($method)) {
            $field->$method($element);
        }
    }

}
