<?php
namespace verbb\formie\models;

use craft\base\Model;

class ReferenceExpression extends Model
{
    // Properties
    // =========================================================================

    public string $raw = '';
    public string $target = '';
    public string $identifier = '';
    public string $selector = '';
    public string $default = '';
    public string $transformerId = '';
    public array $transformerParams = [];
    public bool $isValid = false;


    // Public Methods
    // =========================================================================

    public function toArray(array $fields = [], array $expand = [], $recursive = true): array
    {
        return [
            'raw' => $this->raw,
            'target' => $this->target,
            'identifier' => $this->identifier,
            'selector' => $this->selector,
            'default' => $this->default,
            'transformerId' => $this->transformerId,
            'transformerParams' => $this->transformerParams,
            'isValid' => $this->isValid,
        ];
    }
}
