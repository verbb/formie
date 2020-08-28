<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;

class IntegrationField extends Model
{
    // Constants
    // =========================================================================

    const TYPE_STRING = 'string';
    const TYPE_NUMBER = 'number';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_DATE = 'date';
    const TYPE_DATETIME = 'datetime';
    const TYPE_ARRAY = 'array';


    // Properties
    // =========================================================================

    public $handle;
    public $name;
    public $type;
    public $required;
    public $options = [];


    // Public Methods
    // =========================================================================

    public function getType()
    {
        if ($this->type) {
            return $this->type;
        }

        return self::TYPE_STRING;
    }

}
