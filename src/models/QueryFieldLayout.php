<?php
namespace verbb\formie\models;

use craft\models\FieldLayout as CraftFieldLayout;

class QueryFieldLayout extends CraftFieldLayout
{
    // Properties
    // =========================================================================

    private array $_fields = [];


    // Public Methods
    // =========================================================================

    // Override the regular functionality of a field layout for use in a SubmissionQuery
    public function setCustomFields(array $fields): void
    {
        $this->_fields = $fields;
    }

    public function getCustomFields(): array
    {
        return $this->_fields;
    }
}
