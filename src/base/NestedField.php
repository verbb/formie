<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\FieldLayoutRow;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field as CraftField;
use craft\base\FieldInterface as CraftFieldInterface;
use craft\db\Query;
use craft\db\QueryParam;
use craft\db\Table;
use craft\elements\ElementCollection;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\services\Elements;

use yii\db\ExpressionInterface;

abstract class NestedField extends Field implements NestedFieldInterface
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

    public ?int $nestedLayoutId = null;

    // TODO: remove at the next breakpoint. Still required for Formie 2>3 migration.
    public mixed $contentTable = null;

    private ?FieldLayout $_fieldLayout = null;


    // Public Methods
    // =========================================================================

    public function hasNestedFields(): bool
    {
        return true;
    }

    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'nestedLayoutId';
        $attributes[] = 'contentTable';

        return $attributes;
    }

    public function getFormBuilderSettings(): array
    {
        $settings = parent::getFormBuilderSettings();

        // Return the form builder config for each row
        $settings['rows'] = $this->getFieldLayout()->getFormBuilderConfig()[0]['rows'] ?? [];

        return $settings;
    }

    public function getRows(): array
    {
        return $this->getFieldLayout()->getRows();
    }

    public function setRows(array $rows): void
    {
        foreach ($rows as $key => $row) {
            $rows[$key] = (!($row instanceof FieldLayoutRow)) ? new FieldLayoutRow($row) : $row;
        }

        // Set the rows for the field layout. There's only ever one page for nested fields, and there's always one page for a layout
        $this->getFieldLayout()->getPages()[0]->setRows($rows);
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

    public function getFieldByHandle(string $handle): ?FieldInterface
    {
        $foundField = null;

        foreach ($this->getFields() as $field) {
            if ($field->handle === $handle) {
                $foundField = $field;
            }
        }

        return $foundField;
    }

    public function getCustomFields(): array
    {
        // Required for compatibility with GQL `craft\gql\base\Generator`
        return $this->getFields();
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

        // Also important to set the parent field on each inner field
        foreach ($this->_fieldLayout->getFields() as $field) {
            $field->setParentField($this);
        }

        return $this->_fieldLayout;
    }

    public function setFieldLayout(FieldLayout $fieldLayout): void
    {
        $this->_fieldLayout = $fieldLayout;
    }

    public function validateFieldLayout(): void
    {
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

        // Save the field layout as the last step
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

        return true;
    }

    public function afterDelete(): void
    {
        // Also delete the field layout and any fields
        if ($this->nestedLayoutId) {
            Formie::$plugin->getFields()->deleteLayoutById($this->nestedLayoutId);
        }
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

        $rules[] = [['fieldLayout'], 'validateFieldLayout'];

        return $rules;
    }

    protected function normalizeFieldValidator(string $attribute, mixed $rule, FieldInterface $field, ElementInterface $element, bool $isEmpty): void
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