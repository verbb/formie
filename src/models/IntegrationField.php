<?php
namespace verbb\formie\models;

use craft\base\Model;

class IntegrationField extends Model
{
    // Constants
    // =========================================================================

    public const TYPE_STRING = 'string';
    public const TYPE_NUMBER = 'number';
    public const TYPE_FLOAT = 'float';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_DATE = 'date';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_DATECLASS = 'dateclass';
    public const TYPE_ARRAY = 'array';
    public const TYPE_PHONE = 'phone';


    // Properties
    // =========================================================================

    public ?string $handle = null;
    public ?string $name = null;
    public ?string $type = null;
    public ?string $sourceType = null;
    public ?string $required = null;
    public mixed $defaultValue = null;
    public array $options = [];
    public array $data = [];
    public bool $acceptsHtml = false;


    // Public Methods
    // =========================================================================

    public function getType(): string
    {
        if ($this->type) {
            return $this->type;
        }

        return self::TYPE_STRING;
    }

    /**
     * Whether submission and Group/Repeater summaries can be mapped to this destination, as HTML when it
     * accepts HTML or as plain text otherwise.
     */
    public function acceptsSummaries(): bool
    {
        return $this->getType() === self::TYPE_STRING && !$this->options;
    }
}
