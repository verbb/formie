<?php
namespace verbb\formie\models;

use craft\elements\db\ElementQuery;

use yii\base\Model;

class FakeElementQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    private array $_fieldValues = [];


    // Public Methods
    // =========================================================================

    public function setFieldValues($values, $fieldLayout = null): void
    {
        $element = new FakeElement();

        if ($fieldLayout) {
            $element->fieldLayoutId = $fieldLayout->id;
        }

        $element->setFieldValues($values);

        $this->_fieldValues[] = $element;
    }

    public function one($db = null): Model|array|null
    {
        return $this->_fieldValues[0] ?? null;
    }

    public function all($db = null): array
    {
        return $this->_fieldValues;
    }

    public function exists($db = null): bool
    {
        return (bool)count($this->_fieldValues);
    }

}
