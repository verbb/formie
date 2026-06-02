<?php
namespace verbb\formie\fields\definitions;

use yii\base\BaseObject;

/**
 * Small semantic DTO for the base client-rendered identity of a field.
 */
class FieldClientDefinition extends BaseObject
{
    // Static Methods
    // =========================================================================

    public static function make(string $type = ''): self
    {
        return new self([
            'type' => $type,
        ]);
    }
    

    // Properties
    // =========================================================================

    public string $type = '';
    public array $input = [];


    // Public Methods
    // =========================================================================

    public function withInputDefinition(array $input): self
    {
        $this->input = array_replace_recursive($this->input, $input);

        return $this;
    }

    public function withPlaceholder(?string $placeholder): self
    {
        return $this->withInputDefinition([
            'placeholder' => $placeholder,
        ]);
    }

    public function withInputType(?string $inputType): self
    {
        return $this->withInputDefinition([
            'inputType' => $inputType,
        ]);
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'input' => array_filter($this->input, static function($value) {
                return $value !== null && $value !== [];
            }),
        ], static function($value) {
            return $value !== null && $value !== [];
        });
    }
}
