<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;

use LitEmoji\LitEmoji;

use JsonSerializable;

class IntegrationField extends Model implements JsonSerializable
{
    // Constants
    // =========================================================================

    const TYPE_STRING = 'string';
    const TYPE_NUMBER = 'number';
    const TYPE_FLOAT = 'float';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_DATE = 'date';
    const TYPE_DATETIME = 'datetime';
    const TYPE_ARRAY = 'array';


    // Properties
    // =========================================================================

    public $handle;
    public $type;
    public $required;
    public $options = [];

    private $_name;


    // Public Methods
    // =========================================================================

    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes[] = 'name';

        return $attributes;
    }

    public function getType()
    {
        if ($this->type) {
            return $this->type;
        }

        return self::TYPE_STRING;
    }

    public function setName($value): void
    {
        $this->_name = LitEmoji::unicodeToShortcode($value);
    }

    public function getName(): string
    {
        return LitEmoji::shortcodeToUnicode($this->_name);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function serialize(): array
    {
        // Ensure that we serialize the encoded value for name
        $data = $this->toArray();
        $data['name'] = $this->_name;

        return $data;
    }
}
