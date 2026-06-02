<?php
namespace verbb\formie\models;

use craft\base\Model;

class FieldReferenceSelector extends Model
{
    // Properties
    // =========================================================================

    public string $label = '';
    public string $handle = '';
    public ?string $condition = null;
    public bool $supportsFieldSelect = true;
    public bool $supportsVariablePicker = true;
    public bool $supportsClient = true;
    public array $meta = [];


    // Public Methods
    // =========================================================================

    public static function make(string $handle, string $label): self
    {
        $option = new self();
        $option->handle = $handle;
        $option->label = $label;

        return $option;
    }

    public function when(?string $condition): self
    {
        $this->condition = $condition;

        return $this;
    }

    public function forFieldSelect(bool $enabled = true): self
    {
        $this->supportsFieldSelect = $enabled;

        return $this;
    }

    public function forVariablePicker(bool $enabled = true): self
    {
        $this->supportsVariablePicker = $enabled;

        return $this;
    }

    public function withMeta(array $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    public function forClient(bool $enabled = true): self
    {
        $this->supportsClient = $enabled;

        return $this;
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true): array
    {
        return [
            'label' => $this->label,
            'handle' => $this->handle,
            'condition' => $this->condition,
            'supportsFieldSelect' => $this->supportsFieldSelect,
            'supportsVariablePicker' => $this->supportsVariablePicker,
            'supportsClient' => $this->supportsClient,
            'meta' => $this->meta,
        ];
    }
}
