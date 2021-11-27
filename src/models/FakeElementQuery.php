<?php
namespace verbb\formie\models;

use craft\elements\db\ElementQuery;

class FakeElementQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    private $_fieldValues = [];


    // Public Methods
    // =========================================================================

    /**
     * @return string
     */
    public function setFieldValues($values, $fieldLayout = null)
    {
        $element = new FakeElement();

        if ($fieldLayout) {
            $element->fieldLayoutId = $fieldLayout->id;
        }

        $element->setFieldValues($values);

        $this->_fieldValues[] = $element;
    }

    /**
     * @return string
     */
    public function one($db = null)
    {
        return $this->_fieldValues[0] ?? null;
    }

    /**
     * @return string
     */
    public function all($db = null)
    {
        return $this->_fieldValues;
    }

}
