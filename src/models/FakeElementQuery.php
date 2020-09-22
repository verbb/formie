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
    public function setFieldValues($values)
    {
        $this->_fieldValues = $values;
    }

    /**
     * @return string
     */
    public function one($db = null)
    {
        return $this->_fieldValues;
    }

}
