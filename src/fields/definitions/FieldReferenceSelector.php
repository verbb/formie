<?php
namespace verbb\formie\fields\definitions;

use yii\base\BaseObject;

/**
 * Normalized selector metadata for token UIs and field-reference pickers.
 */
class FieldReferenceSelector extends BaseObject
{
    // Static Methods
    // =========================================================================

    public static function make(string $handle, string $label): self
    {
        return new self([
            'handle' => $handle,
            'label' => $label,
        ]);
    }

    public static function fromArray(array $config): self
    {
        return new self([
            'label' => (string)($config['label'] ?? ''),
            'handle' => (string)($config['handle'] ?? ''),
            'condition' => $config['if'] ?? $config['condition'] ?? null,
            'supportsFieldSelect' => (bool)($config['supportsFieldSelect'] ?? true),
            'supportsVariablePicker' => (bool)($config['supportsVariablePicker'] ?? true),
            'supportsClient' => (bool)($config['supportsClient'] ?? $config['supportsRuntime'] ?? true),
            'meta' => (array)($config['meta'] ?? []),
        ]);
    }
    

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

    public function forClient(bool $enabled = true): self
    {
        $this->supportsClient = $enabled;

        return $this;
    }

    public function withMeta(array $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    public function toArray(): array
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
